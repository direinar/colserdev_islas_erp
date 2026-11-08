<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngresoTanque extends Model
{
    protected $table = 'ingresos_tanques';

    protected $fillable = [
        'fecha',
        'ingreso_corriente',
        'ingreso_acpm',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'ingreso_corriente' => 'decimal:3',
        'ingreso_acpm' => 'decimal:3',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
