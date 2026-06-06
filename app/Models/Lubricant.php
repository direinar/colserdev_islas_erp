<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lubricant extends Model
{
    protected $fillable = [

        'reference',
        'sale_price',
        'iva',
        'total',
        'cost_price',
        'supplier',
        'active'

    ];
}
