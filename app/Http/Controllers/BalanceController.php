<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Payment;
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
            ->where('role', 'employee')
            ->orderByDesc('balance')
            ->orderBy('name')
            ->paginate(20);

        $totalBalance = (float) User::query()->where('role', 'employee')->sum('balance');

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
     * Breakdown page: every debit (money spent), with the purchased product,
     * quantity, unit cost, requisition and Daraz account.
     */
    public function spent(Request $request): View
    {
        $user = $request->user();

        $spending = $this->ledger($user)
            ->where('amount', '<', 0)
            ->with([
                'creator',
                'reference' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    RequisitionItem::class => ['product', 'account', 'requisition'],
                ]),
            ])
            ->latest()
            ->paginate(20);

        return view('balances.spent', [
            'user' => $user,
            'spending' => $spending,
            'totalSpent' => $this->totalSpent($user),
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
