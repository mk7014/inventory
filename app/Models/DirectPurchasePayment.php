<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectPurchasePayment extends Model
{
    protected $fillable = [
        'direct_purchase_id',
        'paid_to',
        'paid_by',
        'amount',
        'payment_method',
        'payment_date',
        'reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'payment_date' => 'datetime',
        ];
    }

    public function directPurchase()
    {
        return $this->belongsTo(DirectPurchase::class);
    }

    public function paidTo()
    {
        return $this->belongsTo(User::class, 'paid_to');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
