<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AforoTanque extends Model
{
    protected $table = 'aforos_tanques';

    protected $fillable = [
        'fecha',
        'vara_corriente',
        'tabla_corriente',
        'vara_acpm',
        'tabla_acpm',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'vara_corriente' => 'decimal:2',
        'tabla_corriente' => 'decimal:3',
        'vara_acpm' => 'decimal:2',
        'tabla_acpm' => 'decimal:3',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
