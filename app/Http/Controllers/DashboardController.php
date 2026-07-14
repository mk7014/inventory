<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Requisition;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    /**
     * The records behind a headline card. Clicking "Sales Income" should show the
     * actual orders, not just a number the user has to trust.
     */
    public function details(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metric' => ['required', Rule::in([
                'revenue', 'cost', 'returns', 'orders', 'pending_delivery',
                'funds', 'spend', 'expenses', 'remaining',
                'requested', 'purchased', 'awaiting_purchase', 'sold', 'stock',
            ])],
            'status' => ['nullable', Rule::enum(SaleStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        [$from, $to] = $this->range($validated);
        $user = $request->user();

        return response()->json($this->dashboard->details(
            $validated['metric'],
            $from,
            $to,
            $user->isAdmin() ? null : $user->id,
            $validated['status'] ?? null,
        ));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(array $validated): array
    {
        return [
            Carbon::parse($validated['from'] ?? now()->startOfMonth())->startOfDay(),
            Carbon::parse($validated['to'] ?? now())->endOfDay(),
        ];
    }

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        [$from, $to] = $this->range($validated);

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
            'pendingDelivery' => $data['pendingDelivery'],
            'pipeline' => $data['pipeline'],
            'wallets' => $data['wallets'],
            'returns' => $data['returns'],
            'profit' => $data['profit'],
            'trend' => $data['trend'],
            'salesTrend' => $data['salesTrend'],
            'lowStock' => $data['lowStock'],
            'recentRequisitions' => $recentRequisitions,
        ]);
    }
}
