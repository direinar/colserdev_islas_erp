<?php

namespace App\Models\Nomina;

use Database\Factories\Nomina\FestivoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Festivo extends Model
{
    /** @use HasFactory<FestivoFactory> */
    use HasFactory;

    protected $table = 'nomina_festivos';

    protected $fillable = [
        'fecha',
        'nombre',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
