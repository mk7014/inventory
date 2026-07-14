<?php

namespace App\Http\Controllers;

use App\Models\DarazAccount;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Requisition;
use App\Models\Sale;
use App\Support\VoidedUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        // Voided users are excluded from every figure here, exactly as on the dashboard.
        $revenue = VoidedUsers::exclude(
            Sale::query()->where('status', 'delivered')->whereBetween('sold_date', [$from, $to])->getQuery(),
            'sales.created_by',
        )->selectRaw('COALESCE(SUM(selling_price * quantity), 0) as total')->value('total');

        $cost = VoidedUsers::exclude(
            Payment::query()->whereBetween('payment_date', [$from, $to])->getQuery(),
            'payments.paid_to',
        )->sum('amount');

        $pending = VoidedUsers::exclude(
            Requisition::query()->where('status', 'pending')->whereBetween('requested_at', [$from, $to])->getQuery(),
            'requisitions.employee_id',
        )->sum('total_amount');

        $voidedIds = VoidedUsers::ids();

        $accountRows = DarazAccount::query()
            ->leftJoin('sales', function ($join) use ($from, $to, $voidedIds) {
                $join->on('daraz_accounts.id', '=', 'sales.daraz_account_id')
                    ->where('sales.status', '=', 'delivered')
                    ->whereBetween('sales.sold_date', [$from, $to]);

                if ($voidedIds !== []) {
                    $join->where(fn ($inner) => $inner->whereNull('sales.created_by')->orWhereNotIn('sales.created_by', $voidedIds));
                }
            })
            ->selectRaw('daraz_accounts.id, daraz_accounts.account_name, daraz_accounts.shop_name, COALESCE(SUM(sales.selling_price * sales.quantity), 0) as revenue')
            ->groupBy('daraz_accounts.id', 'daraz_accounts.account_name', 'daraz_accounts.shop_name')
            ->orderBy('daraz_accounts.account_name')
            ->get();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'summary' => ['revenue' => $revenue, 'cost' => $cost, 'pending' => $pending, 'profit' => $revenue - $cost],
            'accountRows' => $accountRows,
            'stockRows' => Product::query()->orderBy('name')->get(),
            'returnRows' => ProductReturn::query()->whereBetween('return_date', [$from, $to])->latest('return_date')->get(),
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = Sale::query()->with('account')
            ->whereBetween('sold_date', [$from, $to])
            ->tap(fn ($query) => VoidedUsers::exclude($query->getQuery(), 'sales.created_by'))
            ->orderBy('sold_date')
            ->get();

        $csv = "Date,Account,Product,Quantity,Unit Price,Total,Status,Source\n";
        foreach ($rows as $row) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $row->sold_date->format('Y-m-d'),
                $this->csv($row->account?->account_name),
                $this->csv($row->product_name),
                $row->quantity,
                $row->selling_price,
                $row->quantity * $row->selling_price,
                $row->status,
                $row->source,
            );
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales-report-'.$from.'-to-'.$to.'.csv"',
        ]);
    }

    private function range(Request $request): array
    {
        return [
            $request->input('from', now()->startOfMonth()->toDateString()),
            $request->input('to', now()->toDateString()),
        ];
    }

    private function csv(?string $value): string
    {
        return '"'.str_replace('"', '""', (string) $value).'"';
    }
}
