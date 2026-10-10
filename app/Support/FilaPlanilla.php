<?php

namespace App\Support;

/**
 * Reglas comunes de las filas de la planilla de turnos.
 */
class FilaPlanilla
{
    /**
     * Una fila solo se descarta cuando todos sus campos están en blanco. Un 0 digitado es un
     * dato y se guarda (antes las filas en 0 se perdían al guardar).
     *
     * @param  array<string, mixed>  $fila
     * @param  list<string>  $campos
     */
    public static function vacia(array $fila, array $campos): bool
    {
        foreach ($campos as $campo) {
            if (trim((string) ($fila[$campo] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }
}
