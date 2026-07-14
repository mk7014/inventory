<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private StockService $stockService, private AuditService $auditService)
    {
    }

    public function create(array $data, User $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $product = Product::find($data['product_id']);

            // Sales start in the lifecycle at Pending. Inventory is only touched as
            // the order progresses (reserved on Shipped, deducted on Delivered) —
            // see SaleStatusService — so creating a sale no longer moves stock.
            $sale = Sale::create([
                'daraz_account_id' => $data['daraz_account_id'],
                'product_id' => $product?->id,
                'product_name' => $product?->name ?? $data['product_name'],
                'selling_price' => $data['selling_price'],
                'quantity' => $data['quantity'],
                'source' => $data['source'],
                'status' => SaleStatus::Pending->value,
                'stock_state' => 'none',
                'sold_date' => $data['sold_date'],
                'created_by' => $user->id,
            ]);

            $this->auditService->record('sale.created', $sale, null, $sale->toArray());

            return $sale;
        }, 3);
    }
}
