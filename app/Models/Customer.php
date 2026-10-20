<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /**
     * Pesos de la DIAN para el dígito de verificación, desde el último dígito del NIT.
     *
     * @var list<int>
     */
    private const PESOS_DV = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];

    protected $fillable = [
        'name',
        'document',
        'dv',
    ];

    /**
     * Dígito de verificación de la DIAN (módulo 11) para un NIT; null si el documento no tiene dígitos.
     */
    public static function digitoVerificacion(string $nit): ?string
    {
        $digitos = preg_replace('/\D/', '', $nit);

        if ($digitos === '' || strlen($digitos) > count(self::PESOS_DV)) {
            return null;
        }

        $suma = 0;
        foreach (array_reverse(str_split($digitos)) as $posicion => $digito) {
            $suma += (int) $digito * self::PESOS_DV[$posicion];
        }

        $residuo = $suma % 11;

        return (string) ($residuo > 1 ? 11 - $residuo : $residuo);
    }
}
