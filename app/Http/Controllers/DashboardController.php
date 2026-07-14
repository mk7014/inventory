<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now())->endOfDay();

        $user = $request->user();

        // Admins see the company-wide picture; anyone else sees their own wallet
        // figures (sales and profit stay company-wide — they are not per-employee).
        $employeeId = $user->isAdmin() ? null : $user->id;

        $data = $this->dashboard->overview($from, $to, $employeeId);

        $recentRequisitions = Requisition::query()
            ->with('employee:id,name')
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->latest()
            ->limit(6)
            ->get(['id', 'requisition_number', 'employee_id', 'status', 'total_amount', 'requested_at']);

        return view('dashboard.index', [
            'from' => $from,
            'to' => $to,
            'isAdmin' => $user->isAdmin(),
            'funds' => $data['funds'],
            'spend' => $data['spend'],
            'expenseCategories' => $data['expenseCategories'],
            'sales' => $data['sales'],
            'delivered' => $data['delivered'],
            'profit' => $data['profit'],
            'trend' => $data['trend'],
            'salesTrend' => $data['salesTrend'],
            'lowStock' => $data['lowStock'],
            'recentRequisitions' => $recentRequisitions,
        ]);
    }
}
