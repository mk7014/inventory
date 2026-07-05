<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'daraz_account_id',
        'product_id',
        'product_name',
        'selling_price',
        'quantity',
        'source',
        'status',
        'sold_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'quantity' => 'integer',
            'sold_date' => 'date',
        ];
    }

    public function account()
    {
        return $this->belongsTo(DarazAccount::class, 'daraz_account_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returns()
    {
        return $this->hasMany(ProductReturn::class);
    }
}
