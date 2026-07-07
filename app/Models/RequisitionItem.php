<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id',
        'item_type',
        'description',
        'daraz_account_id',
        'product_id',
        'product_name',
        'order_id_daraz',
        'quantity',
        'purchase_price',
        'subtotal',
        'purchased_at',
        'purchased_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity'       => 'integer',
            'purchase_price' => 'decimal:2',
            'subtotal'       => 'decimal:2',
            'purchased_at'   => 'datetime',
        ];
    }

    public function isProductItem(): bool
    {
        return $this->item_type === 'product';
    }

    public function isCostItem(): bool
    {
        return $this->item_type === 'cost';
    }

    public function isPurchased(): bool
    {
        return $this->purchased_at !== null;
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function account()
    {
        return $this->belongsTo(DarazAccount::class, 'daraz_account_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }
}
