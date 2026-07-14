<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

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
     *   - adjust_in               : manual admin correction up      (+qty)
     *   - adjust_out              : manual admin correction down    (-qty)
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

        // Without a surrounding transaction, autocommit releases the row lock the instant
        // the SELECT returns — lockForUpdate below would be a silent no-op and two callers
        // could interleave their read-check-write. Fail loudly rather than corrupt stock.
        if (DB::transactionLevel() === 0) {
            throw new LogicException('StockService::move() must run inside a DB transaction; lockForUpdate() does nothing without one.');
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

            case 'adjust_out':
                // A manual reduction may only eat into stock that is not already
                // reserved for a shipped order, otherwise available stock would
                // go negative and those orders could no longer be fulfilled.
                if ($currentStock - $quantity < $bookedStock) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Cannot remove '.$quantity.' unit(s). Only '.($currentStock - $bookedStock)
                            .' of the '.$currentStock.' in stock are free — the rest is booked for shipped orders.',
                    ]);
                }
                $currentStock -= $quantity;
                $signedQuantity = -$quantity;
                break;

            default: // in_purchase, in_return, adjust_in — additive restocks
                $currentStock += $quantity;
                $signedQuantity = $quantity;
        }

        // forceFill, because current_stock/booked_stock are deliberately NOT mass-assignable:
        // this service is the only sanctioned writer, so nothing else can move stock without
        // also writing the ledger row below.
        $lockedProduct->forceFill([
            'current_stock' => $currentStock,
            'booked_stock' => $bookedStock,
        ])->save();

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
