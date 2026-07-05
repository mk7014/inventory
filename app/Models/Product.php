<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'default_purchase_price', 'current_stock'];

    protected function casts(): array
    {
        return [
            'default_purchase_price' => 'decimal:2',
            'current_stock' => 'integer',
        ];
    }
}
