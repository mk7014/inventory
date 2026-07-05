<?php

namespace App\Http\Controllers;

use App\Models\RequisitionExpense;
use App\Models\Requisition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequisitionExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = RequisitionExpense::with(['requisition', 'creator'])
            ->when(! $request->user()->isAdmin(), function ($q) use ($request) {
                $q->whereHas('requisition', fn ($r) => $r->where('employee_id', $request->user()->id));
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->filled('to'),   fn ($q) => $q->whereDate('expense_date', '<=', $request->to));

        $expenses = $query->latest('expense_date')->paginate(20)->withQueryString();
        $total    = $query->sum('amount');

        return view('expenses.index', compact('expenses', 'total'));
    }

    public function store(Request $request, Requisition $requisition): RedirectResponse
    {
        abort_unless($requisition->employee_id === $request->user()->id, 403);
        abort_unless($requisition->status === 'approved', 403, 'Expenses can only be added to approved requisitions.');

        $data = $request->validate([
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
        ]);

        $requisition->expenses()->create([
            'description'  => $data['description'],
            'amount'       => $data['amount'],
            'expense_date' => $data['expense_date'],
            'created_by'   => $request->user()->id,
        ]);

        return back()->with('success', 'Expense recorded.');
    }
}
