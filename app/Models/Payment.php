<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'requisition_id',
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
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
        ];
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
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
