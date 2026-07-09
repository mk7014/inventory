<?php

namespace App\Http\Controllers;

use App\Http\Requests\DirectPurchaseStoreRequest;
use App\Models\DirectPurchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DirectPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectPurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $query = DirectPurchase::query()
            ->with(['employee', 'supplier', 'warehouse'])
            ->withCount('items');

        if (! $request->user()->isAdmin()) {
            $query->where('employee_id', $request->user()->id);
        }

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('payment_type'), fn ($q) => $q->where('payment_type', $request->payment_type))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('purchase_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('purchase_date', '<=', $request->to));

        // Summary KPIs — scoped to what the user may see, ignoring filters.
        $statsBase = DirectPurchase::query();
        if (! $request->user()->isAdmin()) {
            $statsBase->where('employee_id', $request->user()->id);
        }
        $stats = [
            'total'       => (clone $statsBase)->count(),
            'total_value' => (float) (clone $statsBase)->where('status', 'approved')->sum('grand_total'),
            'pending'     => (clone $statsBase)->where('status', 'pending')->count(),
            'total_due'   => (float) (clone $statsBase)->where('payment_type', 'due')->where('status', 'approved')
                ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) AS agg')->value('agg'),
        ];

        return view('direct-purchases.index', [
            'purchases' => $query->latest()->paginate(15)->withQueryString(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'employees' => $this->employeeOptions($request),
            'stats'     => $stats,
        ]);
    }

    public function create(Request $request): View
    {
        // Admins may raise a purchase for any employee (with a live balance
        // readout); a regular user only ever buys against their own wallet.
        $employees = $request->user()->isAdmin()
            ? User::query()->orderBy('name')->get(['id', 'name', 'balance'])
            : collect();

        return view('direct-purchases.create', [
            'suppliers'  => Supplier::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products'   => Product::query()->orderBy('name')->get(),
            'employees'  => $employees,
        ]);
    }

    public function store(DirectPurchaseStoreRequest $request, DirectPurchaseService $service): RedirectResponse
    {
        $employee = $request->user()->isAdmin() && $request->filled('employee_id')
            ? User::findOrFail($request->employee_id)
            : $request->user();

        $purchase = $service->create($request->validated(), $employee, $request->user());

        return redirect()->route('direct-purchases.show', $purchase)->with('success', 'Direct purchase submitted.');
    }

    public function show(Request $request, DirectPurchase $purchase): View
    {
        abort_unless($request->user()->isAdmin() || $purchase->employee_id === $request->user()->id, 403);

        return view('direct-purchases.show', [
            'purchase' => $purchase->load('employee', 'supplier', 'warehouse', 'approver', 'items.product', 'payments.paidBy'),
        ]);
    }

    /**
     * Due (out-of-pocket) purchase report — outstanding money the company owes
     * employees, with running totals. Admin sees everyone; employees see their own.
     */
    public function due(Request $request): View
    {
        $query = DirectPurchase::query()
            ->with(['employee', 'supplier'])
            ->where('payment_type', 'due')
            ->where('status', 'approved');

        if (! $request->user()->isAdmin()) {
            $query->where('employee_id', $request->user()->id);
        }

        $query->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('outstanding'), fn ($q) => $q->whereColumn('paid_amount', '<', 'grand_total'));

        $purchases = $query->latest('purchase_date')->paginate(20)->withQueryString();

        $base = DirectPurchase::query()->where('payment_type', 'due')->where('status', 'approved');
        if (! $request->user()->isAdmin()) {
            $base->where('employee_id', $request->user()->id);
        }

        return view('direct-purchases.due', [
            'purchases'    => $purchases,
            'suppliers'    => Supplier::query()->orderBy('name')->get(),
            'employees'    => $this->employeeOptions($request),
            'totalDue'     => (float) $base->clone()->sum('grand_total'),
            'totalPaid'    => (float) $base->clone()->sum('paid_amount'),
        ]);
    }

    /**
     * Employee list for filters/selectors — only meaningful to admins; a
     * non-admin only ever sees their own records so no selector is needed.
     */
    private function employeeOptions(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return collect();
        }

        return User::query()->orderBy('name')->get(['id', 'name']);
    }
}
