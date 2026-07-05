<?php

namespace App\Services;

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

            $sale = Sale::create([
                'daraz_account_id' => $data['daraz_account_id'],
                'product_id' => $product?->id,
                'product_name' => $product?->name ?? $data['product_name'],
                'selling_price' => $data['selling_price'],
                'quantity' => $data['quantity'],
                'source' => $data['source'],
                'status' => 'completed',
                'sold_date' => $data['sold_date'],
                'created_by' => $user->id,
            ]);

            if ($sale->source === 'stock' && $product) {
                $this->stockService->move($product, 'out_sale', $sale->quantity, $sale, $user->id);
            }

            $this->auditService->record('sale.created', $sale, null, $sale->toArray());

            return $sale;
        });
    }
}
