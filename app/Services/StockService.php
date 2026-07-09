<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Single choke point for every inventory change. Runs under lockForUpdate so
     * concurrent sales/returns can never over-commit stock.
     *
     * Movement types and their effect on the product:
     *   - in_purchase / in_return : add to current_stock            (+qty)
     *   - book                    : reserve stock                   (booked_stock +qty)
     *   - release                 : cancel a reservation            (booked_stock -qty)
     *   - out_sale                : ship out delivered goods        (current_stock -qty,
     *                               also clears the matching reservation)
     *
     * Available stock (current_stock − booked_stock) is never allowed to go
     * negative on a reservation, and current_stock is never allowed to go
     * negative on an out-sale.
     */
    public function move(Product $product, string $type, int $quantity, Model $reference, ?int $userId = null): StockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }

        $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

        $currentStock = $lockedProduct->current_stock;
        $bookedStock = $lockedProduct->booked_stock;

        switch ($type) {
            case 'book':
                if (($currentStock - $bookedStock) - $quantity < 0) {
                    throw ValidationException::withMessages(['quantity' => 'Not enough available stock to reserve for this order.']);
                }
                $bookedStock += $quantity;
                $signedQuantity = $quantity;
                break;

            case 'release':
                $bookedStock = max(0, $bookedStock - $quantity);
                $signedQuantity = -$quantity;
                break;

            case 'out_sale':
                if ($currentStock - $quantity < 0) {
                    throw ValidationException::withMessages(['quantity' => 'Not enough stock is available for this sale.']);
                }
                $currentStock -= $quantity;
                // The goods were reserved when shipped; releasing the reservation
                // as they leave keeps available stock consistent.
                $bookedStock = max(0, $bookedStock - $quantity);
                $signedQuantity = -$quantity;
                break;

            default: // in_purchase, in_return — additive restocks
                $currentStock += $quantity;
                $signedQuantity = $quantity;
        }

        $lockedProduct->update([
            'current_stock' => $currentStock,
            'booked_stock' => $bookedStock,
        ]);

        return StockMovement::create([
            'product_id' => $lockedProduct->id,
            'product_name' => $lockedProduct->name,
            'type' => $type,
            'quantity' => $signedQuantity,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'created_by' => $userId,
        ]);
    }
}
