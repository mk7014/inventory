<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectPurchaseItem extends Model
{
    protected $fillable = [
        'direct_purchase_id',
        'product_id',
        'product_name',
        'sku',
        'quantity',
        'unit',
        'purchase_price',
        'discount',
        'tax',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity'       => 'integer',
            'purchase_price' => 'decimal:2',
            'discount'       => 'decimal:2',
            'tax'            => 'decimal:2',
            'line_total'     => 'decimal:2',
        ];
    }

    public function directPurchase()
    {
        return $this->belongsTo(DirectPurchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
