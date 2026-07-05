<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'product_name',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'created_by',
    ];
}
