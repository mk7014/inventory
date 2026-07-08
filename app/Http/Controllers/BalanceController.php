<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Payment;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BalanceController extends Controller
{
    /**
     * Admin-only: every employee's current balance with the total credited.
     */
    public function index(): View
    {
        $users = User::query()
            ->whereRelation('role', 'slug', '!=', 'admin')
            ->with('role')
            ->orderByDesc('balance')
            ->orderBy('name')
            ->paginate(20);

        $totalBalance = (float) User::query()->whereRelation('role', 'slug', '!=', 'admin')->sum('balance');

        return view('balances.index', compact('users', 'totalBalance'));
    }

    /**
     * Overview: totals (received / spent / current balance) + recent activity.
     * Each total links to its own dedicated breakdown page.
     */
    public function mine(Request $request): View
    {
        $user = $request->user();

        $recent = $this->ledger($user)->with('creator')->latest()->limit(8)->get();

        return view('balances.mine', [
            'user' => $user,
            'totalCredited' => $this->totalCredited($user),
            'totalSpent' => $this->totalSpent($user),
            'recent' => $recent,
        ]);
    }

    /**
     * Breakdown page: every credit (money paid to the employee), with the
     * originating payment and requisition.
     */
    public function received(Request $request): View
    {
        $user = $request->user();

        $credits = $this->ledger($user)
            ->where('amount', '>', 0)
            ->with([
                'creator',
                'reference' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    Payment::class => ['requisition'],
                ]),
            ])
            ->latest()
            ->paginate(20);

        return view('balances.received', [
            'user' => $user,
            'credits' => $credits,
            'totalCredited' => $this->totalCredited($user),
        ]);
    }

    /**
     * Breakdown page: money spent grouped per requisition. Each row shows the
     * requisition, how many purchases it covers and the total spent — click
     * through to spentRequisition() for the product-by-product breakdown.
     */
    public function spent(Request $request): View
    {
        $user = $request->user();

        $requisitions = $this->ledger($user)
            ->where('balance_transactions.amount', '<', 0)
            ->where('balance_transactions.reference_type', RequisitionItem::class)
            ->join('requisition_items', 'requisition_items.id', '=', 'balance_transactions.reference_id')
            ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->groupBy('requisitions.id', 'requisitions.requisition_number', 'requisitions.status')
            ->orderByRaw('MAX(balance_transactions.created_at) DESC')
            ->selectRaw(
                'requisitions.id as requisition_id, '.
                'requisitions.requisition_number as requisition_number, '.
                'requisitions.status as requisition_status, '.
                'SUM(-balance_transactions.amount) as total_spent, '.
                'COUNT(*) as purchase_count, '.
                'MAX(balance_transactions.created_at) as last_purchase_at'
            )
            ->paginate(20);

        return view('balances.spent', [
            'user' => $user,
            'requisitions' => $requisitions,
            'totalSpent' => $this->totalSpent($user),
        ]);
    }

    /**
     * Drill-down: every purchase (debit) the employee made for a single
     * requisition, product by product, with quantity, unit cost and account.
     */
    public function spentRequisition(Request $request, Requisition $requisition): View
    {
        $user = $request->user();

        $itemIds = $requisition->items()->pluck('id');

        $spending = $this->ledger($user)
            ->where('amount', '<', 0)
            ->where('reference_type', RequisitionItem::class)
            ->whereIn('reference_id', $itemIds)
            ->with([
                'creator',
                'reference' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    RequisitionItem::class => ['product', 'account', 'requisition'],
                ]),
            ])
            ->latest()
            ->paginate(20);

        abort_if($spending->total() === 0, 404);

        return view('balances.spent-requisition', [
            'user' => $user,
            'requisition' => $requisition,
            'spending' => $spending,
            'requisitionTotal' => (float) abs(
                $this->ledger($user)
                    ->where('amount', '<', 0)
                    ->where('reference_type', RequisitionItem::class)
                    ->whereIn('reference_id', $itemIds)
                    ->sum('amount')
            ),
        ]);
    }

    /**
     * Breakdown page: full running statement — how the current balance was
     * built up from every credit and debit.
     */
    public function statement(Request $request): View
    {
        $user = $request->user();

        $transactions = $this->ledger($user)->with('creator')->latest()->paginate(25);

        return view('balances.statement', [
            'user' => $user,
            'transactions' => $transactions,
            'totalCredited' => $this->totalCredited($user),
            'totalSpent' => $this->totalSpent($user),
        ]);
    }

    private function ledger(User $user): Builder
    {
        return BalanceTransaction::query()->where('user_id', $user->id);
    }

    private function totalCredited(User $user): float
    {
        return (float) $this->ledger($user)->where('amount', '>', 0)->sum('amount');
    }

    private function totalSpent(User $user): float
    {
        return (float) abs($this->ledger($user)->where('amount', '<', 0)->sum('amount'));
    }
}
