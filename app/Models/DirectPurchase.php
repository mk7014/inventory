<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectPurchase extends Model
{
    protected $fillable = [
        'purchase_number',
        'employee_id',
        'supplier_id',
        'warehouse_id',
        'payment_type',
        'status',
        'payment_status',
        'purchase_date',
        'invoice_number',
        'reference_number',
        'remarks',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'paid_amount',
        'approved_at',
        'approved_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date'  => 'date',
            'subtotal'       => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total'      => 'decimal:2',
            'grand_total'    => 'decimal:2',
            'paid_amount'    => 'decimal:2',
            'approved_at'    => 'datetime',
        ];
    }

    public function isAdvance(): bool
    {
        return $this->payment_type === 'advance';
    }

    public function isDue(): bool
    {
        return $this->payment_type === 'due';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Outstanding amount still owed to the employee (due purchases only).
     * Advance purchases carry no due — the cost came straight off the wallet.
     */
    public function dueAmount(): float
    {
        if (! $this->isDue()) {
            return 0.0;
        }

        return max((float) $this->grand_total - (float) $this->paid_amount, 0.0);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(DirectPurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(DirectPurchasePayment::class);
    }
}
