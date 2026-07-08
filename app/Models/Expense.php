<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Expense extends Model
{
    /** Selectable expense categories (kept here as the single source of truth). */
    public const CATEGORIES = [
        'Transport',
        'Food & Meals',
        'Office Supplies',
        'Packaging & Delivery',
        'Utilities',
        'Marketing',
        'Communication',
        'Miscellaneous',
    ];

    protected $fillable = [
        'user_id',
        'category',
        'description',
        'amount',
        'expense_date',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The balance ledger rows (debit on create, credit on refund) for this expense. */
    public function transactions(): MorphMany
    {
        return $this->morphMany(BalanceTransaction::class, 'reference');
    }
}
