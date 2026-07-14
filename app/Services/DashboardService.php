<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\User;
use App\Support\VoidedUsers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
        // The voided signature is part of the key: voiding a user must change the
        // numbers immediately, not once the TTL happens to lapse.
        $key = sprintf(
            'dashboard:%s:%s:%s:%s',
            $employeeId ?? 'all',
            $from->toDateString(),
            $to->toDateString(),
            VoidedUsers::signature(),
        );

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
                'pendingDelivery' => $this->pendingDelivery($from, $to),
                'pipeline' => $this->pipeline(),
                'wallets' => $this->wallets(),
                'returns' => $this->returns($from, $to, $profit['gross_sales']),
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
        // Columns are qualified because the drill-down joins `users`, which also has
        // a created_at — an unqualified one would be ambiguous.
        return VoidedUsers::exclude(
            DB::table('balance_transactions')
                ->whereBetween('balance_transactions.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->when($employeeId, fn ($query) => $query->where('balance_transactions.user_id', $employeeId)),
            'balance_transactions.user_id',
        );
    }

    /**
     * The individual records behind a headline figure, so clicking a card can show
     * exactly where the number came from.
     *
     * Every query here reuses the same rules as the card it explains — delivered-only
     * revenue, voided users excluded — so the rows always add up to the figure shown.
     *
     * @return array{title:string, subtitle:string, columns:array, rows:array, total:float, total_label:string}
     */
    public function details(string $metric, Carbon $from, Carbon $to, ?int $employeeId = null, ?string $status = null): array
    {
        return match ($metric) {
            'revenue' => $this->revenueDetails($from, $to),
            'cost' => $this->costDetails($from, $to),
            'returns' => $this->returnDetails($from, $to),
            'orders' => $this->orderDetails($from, $to, $status),
            'pending_delivery' => $this->orderDetails($from, $to, null, self::AWAITING_DELIVERY),
            'funds' => $this->ledgerDetails($from, $to, $employeeId, credits: true),
            'spend' => $this->ledgerDetails($from, $to, $employeeId, credits: false),
            'expenses' => $this->expenseDetails($from, $to, $employeeId),
            'remaining' => $this->walletDetails(),
            'requested' => $this->requisitionLineDetails(null),
            'awaiting_purchase' => $this->requisitionLineDetails(false),
            'purchased' => $this->purchasedDetails(),
            'sold' => $this->soldDetails(),
            'stock' => $this->stockDetails(),
            default => throw new InvalidArgumentException('Unknown metric: '.$metric),
        };
    }

    /**
     * Every order that came back, and whether the goods were salvageable.
     *
     * Driven from `sales.returned_quantity` (not the `returns` table) for the same
     * reason the Returns card is: a sale can be marked returned from the Action menu
     * without a returns row ever being written. The returns table is LEFT JOINed only
     * to recover the condition — where it is missing, that is stated rather than guessed.
     */
    private function returnDetails(Carbon $from, Carbon $to): array
    {
        $rows = $this->salesQuery()
            ->leftJoin('returns', 'returns.sale_id', '=', 'sales.id')
            ->where('sales.returned_quantity', '>', 0)
            ->whereBetween('sales.sold_date', $this->bounds($from, $to))
            ->orderByDesc('sales.sold_date')
            ->limit(200)
            ->get([
                'sales.sold_date', 'sales.product_name', 'sales.quantity',
                'sales.returned_quantity', 'sales.selling_price',
                'returns.condition', 'returns.reason',
                DB::raw('sales.returned_quantity * sales.selling_price as refund'),
            ]);

        return [
            'title' => 'Returned Orders',
            'subtitle' => 'Goods the customer sent back. A "good" return goes back on the shelf and can be sold again; a "damaged" one is a straight loss — you refunded the money AND cannot resell the goods.',
            'columns' => ['Date', 'Product', 'Sold', 'Returned', 'Condition', 'Refunded'],
            'rows' => $rows->map(fn ($row) => [
                Carbon::parse($row->sold_date)->format('d M Y'),
                $row->product_name,
                (string) $row->quantity,
                (string) $row->returned_quantity,
                match ($row->condition) {
                    'good' => 'Good — back in stock',
                    'damaged' => 'Damaged — lost',
                    default => 'Not recorded',
                },
                $this->taka($row->refund),
            ])->all(),
            'total' => round((float) $rows->sum('refund'), 2),
            'total_label' => 'Total refunded to customers',
        ];
    }

    /**
     * Every order in one status — what "12 pending orders" actually consists of.
     *
     * @param  array<string>|null  $statuses  several statuses at once (e.g. "not delivered yet")
     */
    private function orderDetails(Carbon $from, Carbon $to, ?string $status, ?array $statuses = null): array
    {
        $label = match (true) {
            $statuses !== null => 'Waiting to Be Delivered',
            $status !== null => (SaleStatus::tryFrom($status)?->label() ?? ucfirst($status)).' Orders',
            default => 'All Orders',
        };

        $rows = $this->salesQuery()
            ->leftJoin('daraz_accounts', 'daraz_accounts.id', '=', 'sales.daraz_account_id')
            ->when($status, fn ($query) => $query->where('sales.status', $status))
            ->when($statuses, fn ($query) => $query->whereIn('sales.status', $statuses))
            ->whereBetween('sales.sold_date', $this->bounds($from, $to))
            ->orderByDesc('sales.sold_date')
            ->limit(200)
            ->get([
                'sales.sold_date', 'sales.product_name', 'sales.status', 'sales.quantity',
                'sales.returned_quantity', 'sales.selling_price', 'daraz_accounts.account_name',
                DB::raw('sales.selling_price * sales.quantity as line_total'),
            ]);

        return [
            'title' => $label,
            'subtitle' => match (true) {
                $statuses !== null => 'Orders the customer has not received yet. Cancelled and returned orders are not here — those are finished.',
                $status === SaleStatus::Returned->value => 'These orders came back. Anything the customer did NOT send back is still your money.',
                default => 'Every order with this status in the selected period.',
            },
            'columns' => ['Date', 'Product', 'Shop', 'Status', 'Qty', 'Order value'],
            'rows' => $rows->map(fn ($row) => [
                Carbon::parse($row->sold_date)->format('d M Y'),
                $row->product_name,
                $row->account_name ?? '—',
                SaleStatus::tryFrom($row->status)?->label() ?? ucfirst($row->status),
                $row->returned_quantity > 0
                    ? $row->quantity.' ('.$row->returned_quantity.' back)'
                    : (string) $row->quantity,
                $this->taka($row->line_total),
            ])->all(),
            'total' => round((float) $rows->sum('line_total'), 2),
            'total_label' => 'Total order value',
        ];
    }

    /**
     * Product lines on requisitions.
     *
     * @param  bool|null  $purchased  null = all lines, false = only the ones still waiting
     */
    private function requisitionLineDetails(?bool $purchased): array
    {
        $rows = VoidedUsers::exclude(
            DB::table('requisition_items')
                ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
                ->join('users', 'users.id', '=', 'requisitions.employee_id')
                ->where('requisition_items.item_type', 'product')
                ->whereNotIn('requisitions.status', ['rejected'])
                ->when($purchased === false, fn ($query) => $query->whereNull('requisition_items.purchased_at')),
            'requisitions.employee_id',
        )
            ->orderByDesc('requisitions.requested_at')
            ->limit(200)
            ->get([
                'requisitions.requisition_number', 'requisitions.status as req_status',
                'requisition_items.product_name', 'requisition_items.quantity',
                'requisition_items.purchase_price', 'requisition_items.purchased_at',
                'requisition_items.subtotal', 'users.name as employee',
            ]);

        return [
            'title' => $purchased === false ? 'Requested — Not Bought Yet' : 'All Requested Products',
            'subtitle' => $purchased === false
                ? 'Staff asked for these products, and nobody has bought them yet. This is your to-do list.'
                : 'Every product staff have asked for on a requisition (rejected requisitions are not counted).',
            'columns' => ['Requisition', 'Staff', 'Product', 'Qty', 'Status', 'Value'],
            'rows' => $rows->map(fn ($row) => [
                $row->requisition_number,
                $row->employee,
                $row->product_name ?? '—',
                (string) $row->quantity,
                $row->purchased_at
                    ? 'Bought '.Carbon::parse($row->purchased_at)->format('d M Y')
                    : 'Waiting to be bought',
                $this->taka($row->subtotal),
            ])->all(),
            'total' => round((float) $rows->sum('subtotal'), 2),
            'total_label' => $purchased === false ? 'Value still to buy' : 'Total requested value',
        ];
    }

    /** Everything actually bought — through a requisition, or bought directly. */
    private function purchasedDetails(): array
    {
        $viaRequisition = VoidedUsers::exclude(
            DB::table('requisition_items')
                ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
                ->where('requisition_items.item_type', 'product')
                ->whereNotNull('requisition_items.purchased_at'),
            'requisitions.employee_id',
        )->get([
            DB::raw("'Requisition' as source"),
            'requisitions.requisition_number as reference',
            'requisition_items.product_name', 'requisition_items.quantity',
            'requisition_items.subtotal as value',
            'requisition_items.purchased_at as bought_at',
        ]);

        $direct = VoidedUsers::exclude(
            DB::table('direct_purchase_items')
                ->join('direct_purchases', 'direct_purchases.id', '=', 'direct_purchase_items.direct_purchase_id')
                ->where('direct_purchases.status', 'approved'),
            'direct_purchases.employee_id',
        )->get([
            DB::raw("'Direct purchase' as source"),
            'direct_purchases.purchase_number as reference',
            'direct_purchase_items.product_name', 'direct_purchase_items.quantity',
            'direct_purchase_items.line_total as value',
            'direct_purchases.approved_at as bought_at',
        ]);

        $rows = $viaRequisition->concat($direct)->sortByDesc('bought_at')->take(200);

        return [
            'title' => 'Products Bought',
            'subtitle' => 'Goods that were actually purchased and came into stock — either through a requisition, or bought directly from a supplier.',
            'columns' => ['Bought on', 'How', 'Reference', 'Product', 'Qty', 'Cost'],
            'rows' => $rows->map(fn ($row) => [
                $row->bought_at ? Carbon::parse($row->bought_at)->format('d M Y') : '—',
                $row->source,
                $row->reference,
                $row->product_name ?? '—',
                (string) $row->quantity,
                $this->taka($row->value),
            ])->values()->all(),
            'total' => round((float) $rows->sum('value'), 2),
            'total_label' => 'Total spent on goods',
        ];
    }

    /** Who is still holding company money, and how much. */
    private function walletDetails(): array
    {
        $rows = User::query()
            ->notVoided()
            ->where('balance', '<>', 0)
            ->orderByDesc('balance')
            ->get(['name', 'email', 'balance']);

        return [
            'title' => 'Money Still With Staff',
            'subtitle' => 'Cash you handed out that has not been spent yet. This is a live balance — it is not affected by the date filter, exactly like stock on the shelf. A negative balance means the employee paid out of their own pocket and the company owes them.',
            'columns' => ['Staff', 'Email', 'Still holding'],
            'rows' => $rows->map(fn (User $user) => [
                $user->name,
                $user->email,
                ((float) $user->balance < 0 ? '− ' : '').$this->taka(abs((float) $user->balance)),
            ])->all(),
            'total' => round((float) $rows->sum('balance'), 2),
            'total_label' => 'Total still with staff',
        ];
    }

    /** All-time units the customer kept — matches the pipeline tile, which has no date filter. */
    private function soldDetails(): array
    {
        return $this->keptSalesDetails(
            null,
            null,
            'Products Sold',
            'Units the customer received AND kept, across all time. Anything sent back is excluded — those goods are on your shelf again.',
        );
    }

    /** What is still sitting on the shelf, and what it is worth. */
    private function stockDetails(): array
    {
        $rows = DB::table('products')
            ->leftJoinSub($this->productCostQuery(), 'costs', 'costs.product_id', '=', 'products.id')
            ->orderByDesc('products.current_stock')
            ->limit(200)
            ->get([
                'products.name', 'products.sku', 'products.current_stock', 'products.booked_stock',
                DB::raw('COALESCE(costs.unit_cost, products.default_purchase_price, 0) as unit_cost'),
                DB::raw('products.current_stock * COALESCE(costs.unit_cost, products.default_purchase_price, 0) as value'),
            ]);

        return [
            'title' => 'Stock On Hand',
            'subtitle' => 'What is left in the warehouse after everything you sold. "Reserved" units are already promised to a shipped order — they are on the shelf but not free to sell.',
            'columns' => ['Product', 'SKU', 'In stock', 'Reserved', 'Cost each', 'Stock value'],
            'rows' => $rows->map(fn ($row) => [
                $row->name,
                $row->sku ?: '—',
                (string) $row->current_stock,
                $row->booked_stock > 0 ? (string) $row->booked_stock : '—',
                $this->taka($row->unit_cost),
                $this->taka($row->value),
            ])->all(),
            'total' => round((float) $rows->sum('value'), 2),
            'total_label' => 'Total stock value',
        ];
    }

    /**
     * The orders behind "money you kept".
     *
     * Must value each order at what the customer KEPT (quantity − returned_quantity),
     * not at the full order value — otherwise a partial return would make this drill-down
     * disagree with the card it is supposed to explain.
     *
     * @param  Carbon|null  $from  null = no date filter (used by the all-time pipeline tile)
     */
    private function keptSalesDetails(?Carbon $from, ?Carbon $to, string $title, string $subtitle): array
    {
        $rows = $this->fulfilledSales()
            ->leftJoin('daraz_accounts', 'daraz_accounts.id', '=', 'sales.daraz_account_id')
            ->whereRaw('sales.quantity - sales.returned_quantity > 0')
            ->when($from && $to, fn ($query) => $query->whereBetween('sales.sold_date', $this->bounds($from, $to)))
            ->orderByDesc('sales.sold_date')
            ->limit(200)
            ->get([
                'sales.sold_date', 'sales.product_name', 'sales.quantity',
                'sales.returned_quantity', 'sales.selling_price', 'daraz_accounts.account_name',
                DB::raw('(sales.quantity - sales.returned_quantity) as kept'),
                DB::raw('(sales.quantity - sales.returned_quantity) * sales.selling_price as kept_value'),
            ]);

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'columns' => ['Date', 'Product', 'Shop', 'Sold', 'Kept', 'Money kept'],
            'rows' => $rows->map(fn ($row) => [
                Carbon::parse($row->sold_date)->format('d M Y'),
                $row->product_name,
                $row->account_name ?? '—',
                (string) $row->quantity,
                $row->returned_quantity > 0
                    ? $row->kept.' ('.$row->returned_quantity.' sent back)'
                    : (string) $row->kept,
                $this->taka($row->kept_value),
            ])->all(),
            'total' => round((float) $rows->sum('kept_value'), 2),
            'total_label' => 'Total money kept',
        ];
    }

    private function revenueDetails(Carbon $from, Carbon $to): array
    {
        return $this->keptSalesDetails(
            $from,
            $to,
            'Delivered Orders',
            'Orders the customer received and kept. If part of an order was sent back, only the part they kept is counted here — that is the money you actually keep.',
        );
    }

    /** What the delivered goods cost to buy — the other half of gross profit. */
    private function costDetails(Carbon $from, Carbon $to): array
    {
        $rows = $this->salesQuery()
            ->leftJoinSub($this->productCostQuery(), 'costs', 'costs.product_id', '=', 'sales.product_id')
            ->leftJoin('products', 'products.id', '=', 'sales.product_id')
            ->where('sales.status', SaleStatus::Delivered->value)
            ->whereBetween('sales.sold_date', $this->bounds($from, $to))
            ->orderByDesc('sales.sold_date')
            ->limit(200)
            ->get([
                'sales.sold_date', 'sales.product_name', 'sales.quantity',
                DB::raw('COALESCE(costs.unit_cost, products.default_purchase_price, 0) as unit_cost'),
                DB::raw('sales.quantity * COALESCE(costs.unit_cost, products.default_purchase_price, 0) as line_cost'),
            ]);

        return [
            'title' => 'Cost of Products Sold',
            'subtitle' => 'What you originally paid for the goods in those delivered orders. The unit cost is the average of every real purchase of that product.',
            'columns' => ['Date', 'Product', 'Qty', 'Cost each', 'Total cost'],
            'rows' => $rows->map(fn ($row) => [
                Carbon::parse($row->sold_date)->format('d M Y'),
                $row->product_name,
                (string) $row->quantity,
                $this->taka($row->unit_cost),
                $this->taka($row->line_cost),
            ])->all(),
            'total' => round((float) $rows->sum('line_cost'), 2),
            'total_label' => 'Total product cost',
        ];
    }

    /**
     * Wallet credits (money invested in staff) or debits (money they spent).
     *
     * Answers the two questions in order: WHO, then WHAT FOR. Both summaries and the
     * transaction list come from the same query, so the three always agree.
     */
    private function ledgerDetails(Carbon $from, Carbon $to, ?int $employeeId, bool $credits): array
    {
        $base = fn () => $this->ledger($from, $to, $employeeId)
            ->join('users', 'users.id', '=', 'balance_transactions.user_id')
            ->where('balance_transactions.amount', $credits ? '>' : '<', 0);

        // Sign: debits are stored negative, so flip them to read as positive amounts.
        $magnitude = $credits ? 'SUM(balance_transactions.amount)' : 'SUM(-balance_transactions.amount)';

        $byEmployee = $base()
            ->selectRaw("users.name, COUNT(*) as transactions, {$magnitude} as total")
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->get();

        $byPurpose = $base()
            ->selectRaw("balance_transactions.type, COUNT(*) as transactions, {$magnitude} as total")
            ->groupBy('balance_transactions.type')
            ->orderByDesc('total')
            ->get();

        $rows = $base()
            ->orderByDesc('balance_transactions.created_at')
            ->limit(200)
            ->get([
                'balance_transactions.created_at', 'balance_transactions.type',
                'balance_transactions.amount', 'balance_transactions.note', 'users.name',
            ]);

        $total = (float) $byEmployee->sum('total');

        $section = fn (string $title, string $hint, Collection $items, callable $label) => [
            'title' => $title,
            'hint' => $hint,
            'items' => $items->map(fn ($row) => [
                'label' => $label($row),
                'total' => $this->taka($row->total),
                'meta' => $row->transactions.' transaction(s)',
                'percent' => $total > 0 ? round((float) $row->total / $total * 100, 1) : 0.0,
            ])->all(),
        ];

        return [
            'title' => $credits ? 'Total Investment in Staff' : 'Staff Expenses',
            'subtitle' => $credits
                ? 'Every taka the company put into a staff member’s wallet, and what it was for.'
                : 'Every taka that left a staff wallet — buying products, or day-to-day running costs.',
            'sections' => [
                $section(
                    $credits ? 'Which staff received it' : 'Which staff spent it',
                    'Who the money went to',
                    $byEmployee,
                    fn ($row) => $row->name,
                ),
                $section(
                    $credits ? 'What it was given for' : 'What it was spent on',
                    'The purpose behind each taka',
                    $byPurpose,
                    fn ($row) => self::LEDGER_LABELS[$row->type] ?? ucfirst(str_replace('_', ' ', $row->type)),
                ),
            ],
            'columns' => ['Date', 'Staff', 'Purpose', 'Note', 'Amount'],
            'rows' => $rows->map(fn ($row) => [
                Carbon::parse($row->created_at)->format('d M Y'),
                $row->name,
                self::LEDGER_LABELS[$row->type] ?? ucfirst(str_replace('_', ' ', $row->type)),
                $row->note ?: '—',
                $this->taka(abs((float) $row->amount)),
            ])->all(),
            'total' => round($total, 2),
            'total_label' => $credits ? 'Total invested' : 'Total spent',
        ];
    }

    /** Day-to-day running costs — the expenses that come off gross profit. */
    private function expenseDetails(Carbon $from, Carbon $to, ?int $employeeId): array
    {
        $rows = $this->expensesQuery()
            ->join('users', 'users.id', '=', 'expenses.user_id')
            ->when($employeeId, fn ($query) => $query->where('expenses.user_id', $employeeId))
            ->whereBetween('expense_date', $this->bounds($from, $to))
            ->orderByDesc('expense_date')
            ->limit(200)
            ->get([
                'expenses.expense_date', 'expenses.category', 'expenses.description',
                'expenses.amount', 'users.name',
            ]);

        return [
            'title' => 'Running Expenses',
            'subtitle' => 'Day-to-day costs that are not the products themselves — transport, food, office, and so on.',
            'columns' => ['Date', 'Staff', 'Category', 'Description', 'Amount'],
            'rows' => $rows->map(fn ($row) => [
                Carbon::parse($row->expense_date)->format('d M Y'),
                $row->name,
                $row->category,
                $row->description ?: '—',
                $this->taka($row->amount),
            ])->all(),
            'total' => (float) $rows->sum('amount'),
            'total_label' => 'Total running expenses',
        ];
    }

    private function taka(float|string|null $value): string
    {
        return '৳ '.number_format((float) $value, 2);
    }

    /**
     * Inclusive [start-of-day, end-of-day] bounds for a date column.
     *
     * The end bound carries a time component on purpose: a bare '2026-07-14' upper
     * bound excludes a row stored as '2026-07-14 00:00:00', which is exactly how a
     * date lands in SQLite. MySQL compares a DATE column against either form happily,
     * so this is correct on both.
     */
    private function bounds(Carbon $from, Carbon $to): array
    {
        return [
            $from->copy()->startOfDay()->toDateTimeString(),
            $to->copy()->endOfDay()->toDateTimeString(),
        ];
    }

    /**
     * Money handed to staff that they have NOT spent yet — still sitting in their wallets.
     *
     * Deliberately NOT date-filtered, like stock on hand: a balance is a point-in-time
     * fact, not a period total. Within a single month, "funds given − spent" is only the
     * net movement for that month, which is not the same thing as what is left over, and
     * calling that "remaining" would be a lie.
     */
    private function wallets(): array
    {
        $row = User::query()
            ->notVoided()
            ->selectRaw('
                COUNT(CASE WHEN balance <> 0 THEN 1 END) as holders,
                COALESCE(SUM(balance), 0) as total,
                COALESCE(SUM(CASE WHEN balance < 0 THEN -balance ELSE 0 END), 0) as owed
            ')
            ->first();

        return [
            'total' => round((float) $row->total, 2),
            'holders' => (int) $row->holders,
            // A negative wallet means the employee spent out of their own pocket — the
            // company owes them that money back.
            'owed' => round((float) $row->owed, 2),
        ];
    }

    /** Sales, minus anything created by a voided user. The base for every sales figure. */
    private function salesQuery()
    {
        return VoidedUsers::exclude(DB::table('sales'), 'sales.created_by');
    }

    /** Expenses, minus anything belonging to a voided user. */
    private function expensesQuery()
    {
        return VoidedUsers::exclude(DB::table('expenses'), 'expenses.user_id');
    }

    /** Category-level detail for the operational expenses (the `expenses` table). */
    private function expenseCategories(Carbon $from, Carbon $to, ?int $employeeId): array
    {
        $rows = $this->expensesQuery()
            ->whereBetween('expense_date', $this->bounds($from, $to))
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
        $byStatus = $this->salesQuery()
            ->whereBetween('sold_date', $this->bounds($from, $to))
            ->selectRaw('status, COUNT(*) as orders, SUM(quantity) as quantity, SUM(selling_price * quantity) as amount')
            ->groupBy('status')
            ->get();

        $orders = (int) $byStatus->sum('orders');
        $amount = (float) $byStatus->sum('amount');

        // Plain-English meaning for each status, so the breakdown explains itself.
        $meaning = [
            'pending' => 'Ordered, not shipped yet',
            'confirmed' => 'Confirmed, waiting to ship',
            'send_to_courier' => 'Handed to the courier',
            'shipped' => 'On the way — stock is reserved',
            'delivered' => 'Customer got it — money is yours',
            'returned' => 'Came back — money refunded',
            'cancelled' => 'Called off — nothing owed',
        ];

        $statuses = $byStatus->sortByDesc('amount')->map(fn ($row) => [
            'status' => $row->status,
            'label' => SaleStatus::tryFrom($row->status)?->label() ?? ucfirst($row->status),
            'meaning' => $meaning[$row->status] ?? '',
            'badge' => SaleStatus::tryFrom($row->status)?->badgeClasses() ?? 'bg-slate-100 text-slate-700',
            'orders' => (int) $row->orders,
            'quantity' => (int) $row->quantity,
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
        $rows = $this->salesQuery()
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
        $row = $this->salesQuery()
            ->where('status', SaleStatus::Delivered->value)
            ->whereBetween('sold_date', $this->bounds($from, $to))
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

    /** Statuses that mean "the customer has not received it yet, and still might". */
    private const AWAITING_DELIVERY = [
        'pending', 'confirmed', 'send_to_courier', 'shipped',
    ];

    /**
     * Orders still on their way. Cancelled and returned orders are NOT here — those are
     * finished, nobody is waiting for them.
     */
    private function pendingDelivery(Carbon $from, Carbon $to): array
    {
        $row = $this->salesQuery()
            ->whereIn('sales.status', self::AWAITING_DELIVERY)
            ->whereBetween('sales.sold_date', $this->bounds($from, $to))
            ->selectRaw('
                COUNT(*) as orders,
                COALESCE(SUM(sales.quantity), 0) as quantity,
                COALESCE(SUM(sales.selling_price * sales.quantity), 0) as value
            ')
            ->first();

        return [
            'orders' => (int) $row->orders,
            'quantity' => (int) $row->quantity,
            'value' => round((float) $row->value, 2),
        ];
    }

    // ── Product pipeline: requested → purchased → sold → left on the shelf ─────

    /**
     * Where the goods are in their life. Deliberately NOT date-filtered: a requisition
     * raised in March and still unpurchased today is exactly what you want to see, and
     * stock on the shelf has no date at all.
     */
    private function pipeline(): array
    {
        // Product lines on live requisitions (a rejected requisition will never be bought).
        $requisitionLines = fn () => VoidedUsers::exclude(
            DB::table('requisition_items')
                ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
                ->where('requisition_items.item_type', 'product')
                ->whereNotIn('requisitions.status', ['rejected']),
            'requisitions.employee_id',
        );

        $requested = (int) $requisitionLines()->sum('requisition_items.quantity');

        $purchasedViaRequisition = (int) $requisitionLines()
            ->whereNotNull('requisition_items.purchased_at')
            ->sum('requisition_items.quantity');

        $awaitingPurchase = (int) $requisitionLines()
            ->whereNull('requisition_items.purchased_at')
            ->sum('requisition_items.quantity');

        $purchasedDirect = (int) VoidedUsers::exclude(
            DB::table('direct_purchase_items')
                ->join('direct_purchases', 'direct_purchases.id', '=', 'direct_purchase_items.direct_purchase_id')
                ->where('direct_purchases.status', 'approved'),
            'direct_purchases.employee_id',
        )->sum('direct_purchase_items.quantity');

        // Units the customer actually kept — the same rule revenue uses.
        $sold = (int) $this->fulfilledSales()
            ->selectRaw('COALESCE(SUM(sales.quantity - sales.returned_quantity), 0) as sold')
            ->value('sold');

        $stock = DB::table('products')
            ->leftJoinSub($this->productCostQuery(), 'costs', 'costs.product_id', '=', 'products.id')
            ->selectRaw('
                COALESCE(SUM(products.current_stock), 0) as quantity,
                COALESCE(SUM(products.booked_stock), 0) as booked,
                COALESCE(SUM(products.current_stock
                    * COALESCE(costs.unit_cost, products.default_purchase_price, 0)), 0) as value
            ')
            ->first();

        return [
            'requested' => $requested,
            'purchased' => $purchasedViaRequisition + $purchasedDirect,
            'purchased_via_requisition' => $purchasedViaRequisition,
            'purchased_direct' => $purchasedDirect,
            'awaiting_purchase' => $awaitingPurchase,
            'sold' => $sold,
            'stock' => (int) $stock->quantity,
            'stock_booked' => (int) $stock->booked,
            'stock_value' => round((float) $stock->value, 2),
        ];
    }

    // ── 5. Profit ─────────────────────────────────────────────────────────────

    /**
     * Sales that actually reached the customer: delivered, plus returned (a return is
     * only reachable FROM delivered, so those goods shipped too).
     *
     * Treating a returned order as if it never happened is wrong once returns can be
     * partial: sell 5, take 2 back, and you still keep the money for 3. Every figure
     * below therefore works from `quantity - returned_quantity` — the units the
     * customer kept — rather than dropping the whole order.
     */
    private function fulfilledSales()
    {
        return $this->salesQuery()
            ->whereIn('sales.status', [SaleStatus::Delivered->value, SaleStatus::Returned->value]);
    }

    /**
     * Cost of goods sold is priced from what the goods ACTUALLY cost: a weighted
     * average of every real purchase of that product (requisition purchases +
     * approved direct purchases), falling back to the product's default price when
     * it has never been purchased through the system.
     */
    private function profit(Carbon $from, Carbon $to): array
    {
        $sales = $this->fulfilledSales()
            ->whereBetween('sales.sold_date', $this->bounds($from, $to))
            ->selectRaw('
                COALESCE(SUM(sales.selling_price * sales.quantity), 0) as gross_sales,
                COALESCE(SUM(sales.selling_price * sales.returned_quantity), 0) as returned_value,
                COALESCE(SUM(sales.selling_price * (sales.quantity - sales.returned_quantity)), 0) as revenue
            ')
            ->first();

        $grossSales = (float) $sales->gross_sales;
        $returnedValue = (float) $sales->returned_value;
        $revenue = (float) $sales->revenue;

        // Cost of the units the customer KEPT. Goods that came back in good condition
        // are on the shelf again, so they were never a cost.
        $cogs = (float) $this->fulfilledSales()
            ->leftJoinSub($this->productCostQuery(), 'costs', 'costs.product_id', '=', 'sales.product_id')
            ->leftJoin('products', 'products.id', '=', 'sales.product_id')
            ->whereBetween('sales.sold_date', $this->bounds($from, $to))
            ->selectRaw(
                'COALESCE(SUM((sales.quantity - sales.returned_quantity)
                    * COALESCE(costs.unit_cost, products.default_purchase_price, 0)), 0) as cogs'
            )
            ->value('cogs');

        // Damaged returns never went back on the shelf: the goods are gone AND the money
        // was refunded. That is a straight loss, and it belongs nowhere else.
        $damagedLoss = (float) $this->damagedReturnsQuery($from, $to)
            ->leftJoinSub($this->productCostQuery(), 'costs', 'costs.product_id', '=', 'returns.product_id')
            ->leftJoin('products', 'products.id', '=', 'returns.product_id')
            ->selectRaw(
                'COALESCE(SUM(returns.quantity * COALESCE(costs.unit_cost, products.default_purchase_price, 0)), 0) as loss'
            )
            ->value('loss');

        // Operational expenses = personal/office expenses + requisition-level costs.
        $expenses = (float) $this->expensesQuery()
            ->whereBetween('expense_date', $this->bounds($from, $to))
            ->sum('amount');

        // A requisition expense belongs to whoever owns the requisition.
        $requisitionExpenses = (float) VoidedUsers::exclude(
            DB::table('requisition_expenses')
                ->join('requisitions', 'requisitions.id', '=', 'requisition_expenses.requisition_id')
                ->whereBetween('requisition_expenses.expense_date', $this->bounds($from, $to)),
            'requisitions.employee_id',
        )->sum('requisition_expenses.amount');

        // Round to the money scale: the weighted-average unit cost is a division, so the
        // raw sum carries float noise (…333331) that must not reach the UI.
        $cogs = round($cogs, 2);
        $damagedLoss = round($damagedLoss, 2);
        $operating = round($expenses + $requisitionExpenses, 2);
        $gross = round($revenue - $cogs - $damagedLoss, 2);
        $net = round($gross - $operating, 2);

        return [
            'gross_sales' => round($grossSales, 2),
            'returned_value' => round($returnedValue, 2),
            'revenue' => round($revenue, 2),
            'product_cost' => $cogs,
            'damaged_loss' => $damagedLoss,
            'operating_expenses' => $operating,
            'gross_profit' => $gross,
            'net_profit' => $net,
            'gross_margin' => $revenue > 0 ? round($gross / $revenue * 100, 1) : 0.0,
            'net_margin' => $revenue > 0 ? round($net / $revenue * 100, 1) : 0.0,
        ];
    }

    /** Returns that came back damaged — the goods were never restocked. */
    private function damagedReturnsQuery(Carbon $from, Carbon $to)
    {
        return VoidedUsers::exclude(
            DB::table('returns')
                ->join('sales', 'sales.id', '=', 'returns.sale_id')
                ->where('returns.condition', 'damaged')
                ->whereBetween('returns.return_date', $this->bounds($from, $to)),
            'sales.created_by',
        );
    }

    // ── Returns, as their own line ────────────────────────────────────────────

    /**
     * Everything that came back: how much, worth how much, and how much was salvageable.
     *
     * Quantity and value are read from `sales.returned_quantity`, NOT from the `returns`
     * table, because a sale can reach "returned" two ways: through ReturnService (which
     * writes a returns row) or through the sales Action menu (which does not). Deriving
     * this card from the same column the profit line uses is the only way the two can
     * never disagree. The `returns` table is used solely for the good/damaged split,
     * which is the one thing only it knows.
     */
    private function returns(Carbon $from, Carbon $to, float $grossSales): array
    {
        $row = $this->salesQuery()
            ->where('sales.returned_quantity', '>', 0)
            ->whereBetween('sales.sold_date', $this->bounds($from, $to))
            ->selectRaw('
                COUNT(*) as orders,
                COALESCE(SUM(sales.returned_quantity), 0) as quantity,
                COALESCE(SUM(sales.returned_quantity * sales.selling_price), 0) as value
            ')
            ->first();

        $damaged = (int) $this->damagedReturnsQuery($from, $to)->sum('returns.quantity');
        $quantity = (int) $row->quantity;
        $value = (float) $row->value;

        return [
            'orders' => (int) $row->orders,
            'quantity' => $quantity,
            'value' => round($value, 2),
            // Anything not explicitly logged as damaged went back on the shelf.
            'good_quantity' => max(0, $quantity - $damaged),
            'damaged_quantity' => $damaged,
            // Share of everything you sold that came back — the number to watch.
            'rate' => $grossSales > 0 ? round($value / $grossSales * 100, 1) : 0.0,
        ];
    }

    /** Weighted-average unit cost per product across every real purchase. */
    private function productCostQuery()
    {
        // A voided user's purchases leave the cost basis entirely, so the weighted
        // average reflects only the buying that still counts.
        $requisitionPurchases = VoidedUsers::exclude(
            DB::table('requisition_items')
                ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
                ->whereNotNull('requisition_items.purchased_at')
                ->whereNotNull('requisition_items.product_id'),
            'requisitions.employee_id',
        )->selectRaw('requisition_items.product_id, requisition_items.quantity, requisition_items.quantity * requisition_items.purchase_price as cost');

        $directPurchases = VoidedUsers::exclude(
            DB::table('direct_purchase_items')
                ->join('direct_purchases', 'direct_purchases.id', '=', 'direct_purchase_items.direct_purchase_id')
                ->where('direct_purchases.status', 'approved'),
            'direct_purchases.employee_id',
        )->selectRaw('direct_purchase_items.product_id, direct_purchase_items.quantity, direct_purchase_items.quantity * direct_purchase_items.purchase_price as cost');

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
            $this->salesQuery()
                ->where('status', SaleStatus::Delivered->value)
                ->where('sold_date', '>=', $start->toDateString())
                ->selectRaw($this->monthBucket('sold_date')." as bucket, SUM(selling_price * quantity) as total")
                ->groupBy('bucket')
                ->get()
        );

        // Purchase spend = what left wallets to buy goods, from the ledger.
        $purchases = $this->monthlyMap(
            VoidedUsers::exclude(DB::table('balance_transactions'), 'balance_transactions.user_id')
                ->whereIn('type', ['debit_purchase', 'debit_direct_purchase'])
                ->where('created_at', '>=', $start)
                ->selectRaw($this->monthBucket('created_at')." as bucket, SUM(-amount) as total")
                ->groupBy('bucket')
                ->get()
        );

        $expenses = $this->monthlyMap(
            $this->expensesQuery()
                ->where('expense_date', '>=', $start->toDateString())
                ->selectRaw($this->monthBucket('expense_date')." as bucket, SUM(amount) as total")
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
            $this->salesQuery()
                ->where('status', SaleStatus::Delivered->value)
                ->whereBetween('sold_date', $this->bounds($start, $to))
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

    /**
     * The "YYYY-MM" bucket expression for the active driver. MySQL runs production;
     * the test suite runs SQLite, which has no DATE_FORMAT.
     */
    private function monthBucket(string $column): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
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
