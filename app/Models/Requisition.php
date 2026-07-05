<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requisition extends Model
{
    protected $fillable = [
        'requisition_number',
        'employee_id',
        'total_amount',
        'approved_amount',
        'status',
        'admin_note',
        'requested_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses()
    {
        return $this->hasMany(RequisitionExpense::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function totalExpenses(): float
    {
        return (float) $this->expenses->sum('amount');
    }
}
