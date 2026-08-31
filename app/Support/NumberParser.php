<?php

namespace App\Support;

/**
 * Single source of truth for parsing user-typed numbers across the app.
 * Frontend inputs use two conventions only: integer money (never has a
 * fractional part) and 3-decimal quantities (galones/lecturas). Every
 * controller must reuse these instead of writing its own ambiguity rules —
 * duplicated parsers previously drifted apart and silently truncated values
 * like "500.000" to 500 in some fields while working fine in others.
 */
class NumberParser
{
    /**
     * Money fields (consignaciones, descuentos, cartera, qr, transferencias,
     * recaudos, varios, gasolina eds) never carry decimals, so '.' and ','
     * are always thousands separators and can be stripped unconditionally.
     */
    public static function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = preg_replace('/[.,\s]/', '', trim((string) $value));

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    /**
     * Quantity fields (galones, lecturas) can carry real decimals, so a lone
     * '.' or ',' is ambiguous: the separator closest to the end of the
     * string is treated as the decimal point, repeated separators can only
     * be thousands separators.
     */
    public static function quantity(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = trim((string) $value);
        $hasDot = str_contains($clean, '.');
        $hasComma = str_contains($clean, ',');

        if ($hasDot && $hasComma) {
            $clean = strrpos($clean, '.') > strrpos($clean, ',')
                ? str_replace(',', '', $clean)
                : str_replace(',', '.', str_replace('.', '', $clean));
        } elseif ($hasComma) {
            $clean = substr_count($clean, ',') > 1
                ? str_replace(',', '', $clean)
                : str_replace(',', '.', $clean);
        } elseif ($hasDot && substr_count($clean, '.') > 1) {
            $clean = str_replace('.', '', $clean);
        }

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
