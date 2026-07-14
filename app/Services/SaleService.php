<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private StockService $stockService, private AuditService $auditService)
    {
    }

    public function create(array $data, User $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $product = $data['product_id']
                ? Product::query()->whereKey($data['product_id'])->lockForUpdate()->first()
                : null;

            if ($product && $data['source'] === 'stock') {
                $this->assertSellable($product, (int) $data['quantity']);
            }

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

    /**
     * A from-stock sale may only be written against units that are genuinely free.
     * Selling does not reserve anything until Shipped, so "free" means on-hand minus
     * what is booked for shipped orders minus what earlier open sales already claim
     * — without that last term one unit could be sold twice while both sales sit in
     * Pending.
     *
     * Caller holds a lockForUpdate on the product row, and the sum below is a locking
     * read too, so a concurrent sale cannot slip in between this check and the insert.
     */
    private function assertSellable(Product $product, int $quantity): void
    {
        $claimed = (int) Sale::query()->openUnreserved($product->id)->lockForUpdate()->sum('quantity');
        $sellable = $product->availableStock() - $claimed;

        if ($quantity <= $sellable) {
            return;
        }

        if ($sellable <= 0) {
            throw ValidationException::withMessages([
                'product_id' => 'Stock out — '.$product->name.' cannot be sold from stock right now. '
                    .$this->breakdown($product, $claimed),
            ]);
        }

        throw ValidationException::withMessages([
            'quantity' => 'Only '.$sellable.' unit(s) of '.$product->name.' can be sold from stock. '
                .$this->breakdown($product, $claimed),
        ]);
    }

    /** Human-readable reason a product has fewer sellable units than it has on hand. */
    private function breakdown(Product $product, int $claimed): string
    {
        $parts = [];

        if ($product->booked_stock > 0) {
            $parts[] = $product->booked_stock.' booked for shipped order(s)';
        }

        if ($claimed > 0) {
            $parts[] = $claimed.' already sold on open order(s)';
        }

        return 'On hand '.$product->current_stock
            .($parts === [] ? '.' : ', of which '.implode(' and ', $parts).'.');
    }
}
