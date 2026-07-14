<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * current_stock and booked_stock are intentionally absent: they are owned by
     * StockService (which writes them with forceFill alongside a stock_movements
     * ledger row). Mass-assigning them would let a plain $product->update() move
     * stock with no ledger entry and no lock.
     */
    protected $fillable = ['name', 'sku', 'image', 'default_purchase_price'];

    protected function casts(): array
    {
        return [
            'default_purchase_price' => 'decimal:2',
            'current_stock' => 'integer',
            'booked_stock' => 'integer',
        ];
    }

    /**
     * Stock that can still be sold: on-hand minus quantity reserved for orders
     * that have shipped but not yet delivered.
     */
    public function availableStock(): int
    {
        return $this->current_stock - $this->booked_stock;
    }

    /**
     * Public URL for the uploaded product image, or null to fall back to a
     * placeholder. Built with asset() so it honours the serving host.
     */
    public function imageUrl(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return asset('storage/'.ltrim($this->image, '/'));
    }
}
