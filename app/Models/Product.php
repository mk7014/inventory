<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'image', 'default_purchase_price', 'current_stock'];

    protected function casts(): array
    {
        return [
            'default_purchase_price' => 'decimal:2',
            'current_stock' => 'integer',
        ];
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
