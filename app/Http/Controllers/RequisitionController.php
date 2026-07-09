<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequisitionStoreRequest;
use App\Models\DarazAccount;
use App\Models\Product;
use App\Models\Requisition;
use App\Services\RequisitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequisitionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Requisition::query()
            ->with('employee')
            ->withSum('payments as paid_total', 'amount')
            ->withCount([
                'items as product_items_count' => fn ($q) => $q->where('item_type', 'product'),
                'items as purchased_items_count' => fn ($q) => $q->where('item_type', 'product')->whereNotNull('purchased_at'),
            ]);

        if (! $request->user()->isAdmin()) {
            $query->where('employee_id', $request->user()->id);
        }

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('requested_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('requested_at', '<=', $request->to));

        $requisitions = $query->latest()->paginate(15)->withQueryString();

        // Summary KPIs — scoped to what the user may see, ignoring the
        // status/date filters so the header stays a stable overview.
        $statsBase = Requisition::query();
        if (! $request->user()->isAdmin()) {
            $statsBase->where('employee_id', $request->user()->id);
        }
        $stats = [
            'total'           => (clone $statsBase)->count(),
            'pending'         => (clone $statsBase)->where('status', 'pending')->count(),
            'approved'        => (clone $statsBase)->where('status', 'approved')->count(),
            'approved_amount' => (float) (clone $statsBase)->where('status', 'approved')->sum('approved_amount'),
        ];

        return view('requisitions.index', compact('requisitions', 'stats'));
    }

    public function create(): View
    {
        return view('requisitions.create', [
            'accounts' => DarazAccount::active()->orderBy('account_name')->get(),
            'products' => Product::query()->orderBy('name')->get(),
        ]);
    }

    public function store(RequisitionStoreRequest $request, RequisitionService $service): RedirectResponse
    {
        $requisition = $service->create($request->validated(), $request->user());

        return redirect()->route('requisitions.show', $requisition)->with('success', 'Requisition submitted.');
    }

    public function show(Request $request, Requisition $requisition): View
    {
        abort_unless($request->user()->isAdmin() || $requisition->employee_id === $request->user()->id, 403);

        return view('requisitions.show', [
            'requisition' => $requisition->load('employee', 'reviewer', 'items.account', 'items.product', 'payments', 'expenses'),
        ]);
    }
}
