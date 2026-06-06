<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    protected $fillable = [
        'name',
        'price',
        'effective_date',
        'active'
    ];
}
