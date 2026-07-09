<?php

namespace App\Models;

use App\Enums\SaleStatus;
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
        'stock_state',
        'booked_quantity',
        'delivered_quantity',
        'returned_quantity',
        'status_updated_at',
        'status_updated_by',
        'sold_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'quantity' => 'integer',
            'booked_quantity' => 'integer',
            'delivered_quantity' => 'integer',
            'returned_quantity' => 'integer',
            'status_updated_at' => 'datetime',
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

    public function statusHistories()
    {
        return $this->hasMany(SaleStatusHistory::class)->latest();
    }

    /** The status as a structured enum value. */
    public function statusEnum(): SaleStatus
    {
        return SaleStatus::from($this->status);
    }

    /** @return SaleStatus[] valid next statuses for the Action menu. */
    public function nextStatuses(): array
    {
        return $this->statusEnum()->allowedNext();
    }

    /** Whether this sale affects inventory (stock-source with a real product). */
    public function affectsStock(): bool
    {
        return $this->source === 'stock' && $this->product_id !== null;
    }
}
