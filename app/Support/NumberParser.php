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
     * Fields typed in es-CO format with decimals (compras, cartera, anticipos): '.' is
     * always the thousands separator and ',' the decimal point, e.g. "3.000,5" = 3000.5.
     * Not for planilla galones/lecturas, which use quantity().
     */
    public static function decimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = str_replace(',', '.', str_replace(['.', ' '], '', trim((string) $value)));

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    /**
     * Whole-gallon fields of Control de tanques (ingresos de carrotanque, saldo inicial):
     * "3.800" is typed with a thousands dot and means 3800, never 3,8 galones. A dot
     * followed by exactly three digits is a thousands separator; anything else falls
     * back to quantity() so "3800,5" or "3800.5" still keep their decimals.
     */
    public static function wholeQuantity(mixed $value): float
    {
        $clean = trim((string) $value);

        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $clean) === 1) {
            return self::decimal($clean);
        }

        return self::quantity($clean);
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
