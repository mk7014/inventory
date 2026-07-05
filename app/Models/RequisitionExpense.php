<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionExpense extends Model
{
    protected $fillable = [
        'requisition_id',
        'description',
        'amount',
        'expense_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
