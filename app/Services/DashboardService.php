<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Every number on the dashboard, derived from real records with aggregate SQL.
 *
 * Two conventions from the existing business logic are respected throughout:
 *   - Revenue counts DELIVERED sales only (a return flips status to `returned`,
 *     so returned orders drop out of revenue automatically). Same rule as
 *     ReportController and the old DashboardController.
 *   - Money in/out of an employee's wallet is read from `balance_transactions`,
 *     the append-only ledger BalanceService writes. Credits are funds given to
 *     the employee, debits are funds they spent. The breakdown is grouped by the
 *     ledger `type` column, so a new funding route added later shows up here with
 *     no change to this service.
 */
class DashboardService
{
    /** Ledger type → human label. Unknown types fall back to a humanised slug. */
    private const LEDGER_LABELS = [
        'credit_payment' => 'Requisition Payment',
        'credit' => 'Manual Fund Allocation',
        'credit_expense_refund' => 'Expense Refund',
        'credit_reversal' => 'Reversal (deleted record)',
        'debit_purchase' => 'Purchase via Requisition',
        'debit_direct_purchase' => 'Direct Purchase',
        'debit_expense' => 'Office & Other Expenses',
        'debit' => 'Other Debit',
        'debit_reversal' => 'Reversal (deleted record)',
    ];

    /**
     * @param  int|null  $employeeId  null = company-wide (admin); otherwise scope wallet
     *                                figures to that employee.
     */
    public function overview(Carbon $from, Carbon $to, ?int $employeeId = null): array
    {
        $key = sprintf('dashboard:%s:%s:%s', $employeeId ?? 'all', $from->toDateString(), $to->toDateString());

        // Short TTL: the dashboard is read constantly but a minute of staleness on an
        // executive summary is harmless, and it keeps the aggregate scans off the hot path.
        return Cache::remember($key, now()->addMinutes(2), function () use ($from, $to, $employeeId) {
            $sales = $this->sales($from, $to);
            $profit = $this->profit($from, $to);

            return [
                'funds' => $this->funds($from, $to, $employeeId),
                'spend' => $this->spend($from, $to, $employeeId),
                'expenseCategories' => $this->expenseCategories($from, $to, $employeeId),
                'sales' => $sales,
                'delivered' => $this->delivered($from, $to, $sales['orders'], $sales['quantity']),
                'profit' => $profit,
                'trend' => $this->monthlyTrend(),
                'salesTrend' => $this->dailySalesTrend($from, $to),
                'lowStock' => $this->lowStock(),
            ];
        });
    }

    // ── 1. Employee fund summary — every credit that reached a wallet ──────────

    private function funds(Carbon $from, Carbon $to, ?int $employeeId): array
    {
        $rows = $this->ledger($from, $to, $employeeId)
            ->where('amount', '>', 0)
            ->selectRaw('type, COUNT(*) as transactions, SUM(amount) as total')
            ->groupBy('type')
            ->get();

        return $this->breakdown($rows);
    }

    // ── 2. Employee expense summary — every debit out of a wallet ─────────────

    private function spend(Carbon $from, Carbon $to, ?int $employeeId): array
    {
        $rows = $this->ledger($from, $to, $employeeId)
            ->where('amount', '<', 0)
            // Debits are stored negative so SUM(amount) still equals the balance; flip
            // the sign here so the card reads as a positive "spent" figure.
            ->selectRaw('type, COUNT(*) as transactions, SUM(-amount) as total')
            ->groupBy('type')
            ->get();

        $summary = $this->breakdown($rows);

        // Split procurement (goods) from operational spend, which is what the
        // "Product Purchase vs Other" figures on the card mean.
        $productTypes = ['debit_purchase', 'debit_direct_purchase'];

        $summary['product_total'] = $rows->whereIn('type', $productTypes)->sum('total');
        $summary['other_total'] = $summary['total'] - $summary['product_total'];

        return $summary;
    }

    /** Shared shape: total, transaction count, and per-source rows with a percentage. */
    private function breakdown(Collection $rows): array
    {
        $total = (float) $rows->sum('total');

        $items = $rows
            ->sortByDesc('total')
            ->map(fn ($row) => [
                'type' => $row->type,
                'label' => self::LEDGER_LABELS[$row->type] ?? ucfirst(str_replace('_', ' ', $row->type)),
                'total' => (float) $row->total,
                'transactions' => (int) $row->transactions,
                'percent' => $total > 0 ? round((float) $row->total / $total * 100, 1) : 0.0,
            ])
            ->values()
            ->all();

        return [
            'total' => $total,
            'transactions' => (int) $rows->sum('transactions'),
            'items' => $items,
        ];
    }

    private function ledger(Carbon $from, Carbon $to, ?int $employeeId)
    {
        return DB::table('balance_transactions')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($employeeId, fn ($query) => $query->where('user_id', $employeeId));
    }

    /** Category-level detail for the operational expenses (the `expenses` table). */
    private function expenseCategories(Carbon $from, Carbon $to, ?int $employeeId): array
    {
        $rows = DB::table('expenses')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->when($employeeId, fn ($query) => $query->where('user_id', $employeeId))
            ->selectRaw('category, COUNT(*) as transactions, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        $total = (float) $rows->sum('total');

        return [
            'total' => $total,
            'transactions' => (int) $rows->sum('transactions'),
            'items' => $rows->sortByDesc('total')->map(fn ($row) => [
                'label' => $row->category,
                'total' => (float) $row->total,
                'transactions' => (int) $row->transactions,
                'percent' => $total > 0 ? round((float) $row->total / $total * 100, 1) : 0.0,
            ])->values()->all(),
        ];
    }

    // ── 3. Sales analytics ────────────────────────────────────────────────────

    private function sales(Carbon $from, Carbon $to): array
    {
        $byStatus = DB::table('sales')
            ->whereBetween('sold_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, COUNT(*) as orders, SUM(quantity) as quantity, SUM(selling_price * quantity) as amount')
            ->groupBy('status')
            ->get();

        $orders = (int) $byStatus->sum('orders');
        $amount = (float) $byStatus->sum('amount');

        $statuses = $byStatus->sortByDesc('amount')->map(fn ($row) => [
            'status' => $row->status,
            'label' => SaleStatus::tryFrom($row->status)?->label() ?? ucfirst($row->status),
            'badge' => SaleStatus::tryFrom($row->status)?->badgeClasses() ?? 'bg-slate-100 text-slate-700',
            'orders' => (int) $row->orders,
            'amount' => (float) $row->amount,
            'percent' => $orders > 0 ? round((int) $row->orders / $orders * 100, 1) : 0.0,
        ])->values()->all();

        return [
            'amount' => $amount,
            'orders' => $orders,
            'quantity' => (int) $byStatus->sum('quantity'),
            'average_order_value' => $orders > 0 ? round($amount / $orders, 2) : 0.0,
            'statuses' => $statuses,
            'periods' => $this->salesPeriods(),
        ];
    }

    /** Today / this week / this month / this year revenue, in one grouped pass. */
    private function salesPeriods(): array
    {
        $rows = DB::table('sales')
            ->where('status', SaleStatus::Delivered->value)
            ->where('sold_date', '>=', now()->startOfYear()->toDateString())
            ->selectRaw('sold_date, SUM(selling_price * quantity) as amount')
            ->groupBy('sold_date')
            ->get();

        $sum = fn (Carbon $start) => (float) $rows
            ->where('sold_date', '>=', $start->toDateString())
            ->sum('amount');

        return [
            'daily' => $sum(now()->startOfDay()),
            'weekly' => $sum(now()->startOfWeek()),
            'monthly' => $sum(now()->startOfMonth()),
            'yearly' => (float) $rows->sum('amount'),
        ];
    }

    // ── 4. Delivered sales ────────────────────────────────────────────────────

    private function delivered(Carbon $from, Carbon $to, int $totalOrders, int $totalQuantity): array
    {
        $row = DB::table('sales')
            ->where('status', SaleStatus::Delivered->value)
            ->whereBetween('sold_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(quantity), 0) as quantity, COALESCE(SUM(selling_price * quantity), 0) as revenue')
            ->first();

        return [
            'orders' => (int) $row->orders,
            'quantity' => (int) $row->quantity,
            'revenue' => (float) $row->revenue,
            'order_percent' => $totalOrders > 0 ? round((int) $row->orders / $totalOrders * 100, 1) : 0.0,
            'quantity_percent' => $totalQuantity > 0 ? round((int) $row->quantity / $totalQuantity * 100, 1) : 0.0,
        ];
    }

    // ── 5. Profit ─────────────────────────────────────────────────────────────

    /**
     * Cost of goods sold is priced from what the goods ACTUALLY cost: a weighted
     * average of every real purchase of that product (requisition purchases +
     * approved direct purchases), falling back to the product's default price when
     * it has never been purchased through the system.
     */
    private function profit(Carbon $from, Carbon $to): array
    {
        $revenueRow = DB::table('sales')
            ->where('status', SaleStatus::Delivered->value)
            ->whereBetween('sold_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(selling_price * quantity), 0) as revenue')
            ->first();

        $revenue = (float) $revenueRow->revenue;

        $cogs = (float) DB::table('sales')
            ->leftJoinSub($this->productCostQuery(), 'costs', 'costs.product_id', '=', 'sales.product_id')
            ->leftJoin('products', 'products.id', '=', 'sales.product_id')
            ->where('sales.status', SaleStatus::Delivered->value)
            ->whereBetween('sales.sold_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw(
                'COALESCE(SUM(sales.quantity * COALESCE(costs.unit_cost, products.default_purchase_price, 0)), 0) as cogs'
            )
            ->value('cogs');

        // Operational expenses = personal/office expenses + requisition-level costs.
        $expenses = (float) DB::table('expenses')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $requisitionExpenses = (float) DB::table('requisition_expenses')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        // Round to the money scale: the weighted-average unit cost is a division, so the
        // raw sum carries float noise (…333331) that must not reach the UI.
        $cogs = round($cogs, 2);
        $operating = round($expenses + $requisitionExpenses, 2);
        $gross = round($revenue - $cogs, 2);
        $net = round($gross - $operating, 2);

        return [
            'revenue' => $revenue,
            'product_cost' => $cogs,
            'operating_expenses' => $operating,
            'gross_profit' => $gross,
            'net_profit' => $net,
            'gross_margin' => $revenue > 0 ? round($gross / $revenue * 100, 1) : 0.0,
            'net_margin' => $revenue > 0 ? round($net / $revenue * 100, 1) : 0.0,
        ];
    }

    /** Weighted-average unit cost per product across every real purchase. */
    private function productCostQuery()
    {
        $requisitionPurchases = DB::table('requisition_items')
            ->whereNotNull('purchased_at')
            ->whereNotNull('product_id')
            ->selectRaw('product_id, quantity, quantity * purchase_price as cost');

        $directPurchases = DB::table('direct_purchase_items')
            ->join('direct_purchases', 'direct_purchases.id', '=', 'direct_purchase_items.direct_purchase_id')
            ->where('direct_purchases.status', 'approved')
            ->selectRaw('direct_purchase_items.product_id, direct_purchase_items.quantity, direct_purchase_items.quantity * direct_purchase_items.purchase_price as cost');

        return DB::query()
            ->fromSub($requisitionPurchases->unionAll($directPurchases), 'purchases')
            ->selectRaw('product_id, SUM(cost) / NULLIF(SUM(quantity), 0) as unit_cost')
            ->groupBy('product_id');
    }

    // ── 7. Trends ─────────────────────────────────────────────────────────────

    /** Last 12 months of revenue, purchase spend, expenses and derived profit. */
    private function monthlyTrend(): array
    {
        $start = now()->copy()->subMonths(11)->startOfMonth();

        $revenue = $this->monthlyMap(
            DB::table('sales')
                ->where('status', SaleStatus::Delivered->value)
                ->where('sold_date', '>=', $start->toDateString())
                ->selectRaw("DATE_FORMAT(sold_date, '%Y-%m') as bucket, SUM(selling_price * quantity) as total")
                ->groupBy('bucket')
                ->get()
        );

        // Purchase spend = what left wallets to buy goods, from the ledger.
        $purchases = $this->monthlyMap(
            DB::table('balance_transactions')
                ->whereIn('type', ['debit_purchase', 'debit_direct_purchase'])
                ->where('created_at', '>=', $start)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, SUM(-amount) as total")
                ->groupBy('bucket')
                ->get()
        );

        $expenses = $this->monthlyMap(
            DB::table('expenses')
                ->where('expense_date', '>=', $start->toDateString())
                ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as bucket, SUM(amount) as total")
                ->groupBy('bucket')
                ->get()
        );

        $labels = [];
        $revenueSeries = $purchaseSeries = $expenseSeries = $profitSeries = [];

        foreach (range(0, 11) as $offset) {
            $month = $start->copy()->addMonths($offset);
            $bucket = $month->format('Y-m');

            $labels[] = $month->format('M y');
            $revenueSeries[] = round($revenue[$bucket] ?? 0, 2);
            $purchaseSeries[] = round($purchases[$bucket] ?? 0, 2);
            $expenseSeries[] = round($expenses[$bucket] ?? 0, 2);
            $profitSeries[] = round(($revenue[$bucket] ?? 0) - ($purchases[$bucket] ?? 0) - ($expenses[$bucket] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenueSeries,
            'purchases' => $purchaseSeries,
            'expenses' => $expenseSeries,
            'profit' => $profitSeries,
        ];
    }

    /** Daily delivered revenue across the selected range (capped to a sane width). */
    private function dailySalesTrend(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->max($to->copy()->subDays(59));

        $rows = $this->dailyMap(
            DB::table('sales')
                ->where('status', SaleStatus::Delivered->value)
                ->whereBetween('sold_date', [$start->toDateString(), $to->toDateString()])
                ->selectRaw('sold_date as bucket, SUM(selling_price * quantity) as total')
                ->groupBy('bucket')
                ->get()
        );

        $labels = [];
        $series = [];

        for ($day = $start->copy(); $day->lte($to); $day->addDay()) {
            $labels[] = $day->format('d M');
            $series[] = round($rows[$day->toDateString()] ?? 0, 2);
        }

        return ['labels' => $labels, 'revenue' => $series];
    }

    private function monthlyMap(Collection $rows): array
    {
        return $rows->mapWithKeys(fn ($row) => [$row->bucket => (float) $row->total])->all();
    }

    private function dailyMap(Collection $rows): array
    {
        return $rows->mapWithKeys(fn ($row) => [
            Carbon::parse($row->bucket)->toDateString() => (float) $row->total,
        ])->all();
    }

    private function lowStock(): Collection
    {
        return Product::query()
            ->where('current_stock', '<=', 3)
            ->orderBy('current_stock')
            ->limit(6)
            ->get(['id', 'name', 'sku', 'current_stock', 'booked_stock']);
    }
}
