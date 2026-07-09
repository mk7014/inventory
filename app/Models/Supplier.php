<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'phone', 'email', 'address', 'status'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function directPurchases()
    {
        return $this->hasMany(DirectPurchase::class);
    }
}
