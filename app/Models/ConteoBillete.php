<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConteoBillete extends Model
{
    protected $fillable = ['conteo_id', 'denominacion', 'valor'];

    protected $casts = ['valor' => 'decimal:2'];

    public function conteo()
    {
        return $this->belongsTo(Conteo::class);
    }
}
