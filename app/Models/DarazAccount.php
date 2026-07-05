<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DarazAccount extends Model
{
    use SoftDeletes;

    protected $fillable = ['account_name', 'shop_name', 'status'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'daraz_account_id');
    }
}
