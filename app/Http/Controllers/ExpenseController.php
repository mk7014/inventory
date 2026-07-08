<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseStoreRequest;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $service)
    {
    }

    /**
     * List page: the create form plus the user's own expenses (all users' when an
     * admin, with an optional per-user filter) and quick summary tiles.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $this->scopedQuery($request);
        $expenses = (clone $query)->with(['user', 'creator'])->latest('expense_date')->latest('id')->paginate(15)->withQueryString();

        return view('expenses.index', [
            'expenses'   => $expenses,
            'total'      => (float) (clone $query)->sum('amount'),
            'count'      => (clone $query)->count(),
            'monthTotal' => (float) (clone $query)->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'balance'    => (float) $user->balance,
            'categories' => Expense::CATEGORIES,
            'isAdmin'    => $user->isAdmin(),
            'employees'  => $user->isAdmin() ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function store(ExpenseStoreRequest $request): RedirectResponse
    {
        $this->service->record($request->user(), $request->validated(), $request->user());

        return redirect()->route('expenses.index')->with('success', 'Expense recorded and deducted from your balance.');
    }

    /**
     * Report / breakdown: totals, category split (for the chart) and a monthly
     * trend, honouring the same scope + date filters as the list.
     */
    public function report(Request $request): View
    {
        $user = $request->user();
        $query = $this->scopedQuery($request);

        $byCategory = (clone $query)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $byMonth = (clone $query)
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return view('expenses.report', [
            'total'      => (float) (clone $query)->sum('amount'),
            'count'      => (clone $query)->count(),
            'byCategory' => $byCategory,
            'byMonth'    => $byMonth,
            'isAdmin'    => $user->isAdmin(),
            'employees'  => $user->isAdmin() ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'filters'    => $request->only(['from', 'to', 'category', 'user_id']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->scopedQuery($request)->with('user')->orderBy('expense_date')->get();
        $filename = 'expenses-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'User', 'Category', 'Description', 'Amount', 'Note']);
            foreach ($rows as $e) {
                fputcsv($out, [
                    $e->expense_date->format('Y-m-d'),
                    $e->user?->name,
                    $e->category,
                    $e->description,
                    number_format((float) $e->amount, 2, '.', ''),
                    $e->note,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $expense->user_id === $request->user()->id, 403);

        $this->service->remove($expense, $request->user());

        return back()->with('success', 'Expense deleted and amount refunded to the balance.');
    }

    /**
     * Base query scoped to the current user (admins see everyone) plus the shared
     * date / category / user filters used by the list, report and export.
     */
    private function scopedQuery(Request $request): Builder
    {
        $user = $request->user();

        return Expense::query()
            ->when(! $user->isAdmin(), fn (Builder $q) => $q->where('user_id', $user->id))
            ->when($user->isAdmin() && $request->filled('user_id'), fn (Builder $q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('category'), fn (Builder $q) => $q->where('category', $request->string('category')))
            ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('expense_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('expense_date', '<=', $request->date('to')));
    }
}
