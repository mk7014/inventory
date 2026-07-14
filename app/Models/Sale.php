<?php

namespace App\Models;

use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    /**
     * Open from-stock sales for a product whose units are spoken for but not yet
     * reserved: a sale only books stock on Shipped, so a Pending one holds no
     * booked_stock at all. Availability checks must subtract these too, otherwise
     * two Pending sales can be written against the same single unit.
     *
     * Cancelled is excluded by status, not by stock_state: a sale cancelled while
     * still Pending never reserved anything, so its stock_state stays 'none'.
     */
    public function scopeOpenUnreserved(Builder $query, int $productId): Builder
    {
        return $query
            ->where('product_id', $productId)
            ->where('source', 'stock')
            ->where('stock_state', 'none')
            ->whereNotIn('status', [
                SaleStatus::Delivered->value,
                SaleStatus::Returned->value,
                SaleStatus::Cancelled->value,
            ]);
    }

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

    /**
     * Whether this sale affects inventory. Any sale tied to a real product moves
     * stock (reserved on Shipped, deducted on Delivered) regardless of its source
     * label — a product-less manual sale carries the status only.
     */
    public function affectsStock(): bool
    {
        return $this->product_id !== null;
    }
}
