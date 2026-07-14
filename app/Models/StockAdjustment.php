<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    /**
     * Reasons an admin can pick from, split by direction. The keys are stored;
     * the labels are what the form and the history table render.
     */
    public const INCREASE_REASONS = [
        'count_correction'  => 'Stock Count Correction',
        'found'             => 'Found / Extra Stock',
        'supplier_bonus'    => 'Supplier Bonus / Free Item',
        'unrecorded_return' => 'Unrecorded Customer Return',
        'opening_balance'   => 'Opening Balance',
        'other'             => 'Other',
    ];

    public const DECREASE_REASONS = [
        'count_correction' => 'Stock Count Correction',
        'damaged'          => 'Damaged / Broken',
        'lost'             => 'Lost / Missing',
        'expired'          => 'Expired',
        'theft'            => 'Theft',
        'sample'           => 'Sample / Giveaway',
        'other'            => 'Other',
    ];

    protected $fillable = [
        'product_id',
        'product_name',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reason',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Reasons valid for a given direction. */
    public static function reasonsFor(string $type): array
    {
        return $type === 'increase' ? self::INCREASE_REASONS : self::DECREASE_REASONS;
    }

    public function reasonLabel(): string
    {
        return self::reasonsFor($this->type)[$this->reason] ?? ucfirst(str_replace('_', ' ', $this->reason));
    }

    /** Signed change, for display: +5 / −5. */
    public function signedQuantity(): int
    {
        return $this->type === 'increase' ? $this->quantity : -$this->quantity;
    }
}
