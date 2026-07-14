<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(
        private StockService $stockService,
        private AuditService $auditService,
    ) {
    }

    /**
     * Manually correct a product's on-hand stock. The adjustment row is the
     * reference document for the stock movement, so every manual change lands in
     * the same ledger as purchases, sales and returns — nothing moves stock
     * outside StockService.
     *
     * The product is row-locked for the whole transaction so the before/after
     * snapshot stored on the adjustment matches what the ledger actually applied.
     * A decrease that would cut into booked stock is rejected inside StockService,
     * which rolls the adjustment row back with it.
     */
    public function record(Product $product, array $data, User $actor): StockAdjustment
    {
        return DB::transaction(function () use ($product, $data, $actor) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $type = $data['type'];
            $quantity = (int) $data['quantity'];
            $before = $locked->current_stock;
            $after = $type === 'increase' ? $before + $quantity : $before - $quantity;

            $adjustment = StockAdjustment::create([
                'product_id'   => $locked->id,
                'product_name' => $locked->name,
                'type'         => $type,
                'quantity'     => $quantity,
                'stock_before' => $before,
                'stock_after'  => $after,
                'reason'       => $data['reason'],
                'note'         => $data['note'] ?? null,
                'created_by'   => $actor->id,
            ]);

            $this->stockService->move(
                $locked,
                $type === 'increase' ? 'adjust_in' : 'adjust_out',
                $quantity,
                $adjustment,
                $actor->id,
            );

            $this->auditService->record(
                'stock.adjusted',
                $adjustment,
                ['current_stock' => $before],
                ['current_stock' => $after],
                $adjustment->reasonLabel(),
            );

            return $adjustment;
        });
    }
}
