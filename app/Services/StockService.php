<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function move(Product $product, string $type, int $quantity, Model $reference, ?int $userId = null): StockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }

        $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
        $signedQuantity = $type === 'out_sale' ? -$quantity : $quantity;
        $newStock = $lockedProduct->current_stock + $signedQuantity;

        if ($newStock < 0) {
            throw ValidationException::withMessages(['quantity' => 'Not enough stock is available for this sale.']);
        }

        $lockedProduct->update(['current_stock' => $newStock]);

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
