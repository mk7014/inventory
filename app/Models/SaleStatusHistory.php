<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleStatusHistory extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'previous_status',
        'new_status',
        'movement_type',
        'quantity',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
