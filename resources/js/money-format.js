// Fuente única de verdad para parsear/formatear números en toda la planilla.
// Antes cada script (galones.js, medios_pago.blade.php, resumen_recibido_turno.blade.php)
// reimplementaba su propia lógica de ambigüedad '.' vs ',', y cada copia divergía con
// el tiempo, causando que valores como "500.000" se truncaran a 500 en unos campos y no
// en otros. Toda la UI debe llamar a window.MoneyFormat en vez de reimplementar esto.

/**
 * Campos de dinero (consignaciones, descuentos, cartera, qr, transferencias,
 * recaudos, varios, gasolina eds): nunca tienen decimales, así que '.' y ','
 * son siempre separadores de miles y se eliminan sin ambigüedad.
 */
function parseMoney(value) {
    if (value === null || value === undefined) return NaN;
    const s = String(value).replace(/[.,\s]/g, '');
    if (s === '') return NaN;
    return parseFloat(s);
}

/**
 * Campos con decimales reales (galones, lecturas de surtidor): un solo '.' o
 * ',' puede ser separador decimal legítimo, así que se resuelve por posición
 * (el separador que aparece más a la derecha es el decimal) y por repetición
 * (varios separadores iguales solo pueden ser de miles).
 */
function parseQty(value) {
    if (value === null || value === undefined) return NaN;
    const s = String(value).trim().replace(/\s+/g, '');
    if (s === '') return NaN;

    const hasDot = s.indexOf('.') !== -1;
    const hasComma = s.indexOf(',') !== -1;

    if (hasDot && hasComma) {
        return s.lastIndexOf('.') > s.lastIndexOf(',')
            ? parseFloat(s.replace(/,/g, ''))
            : parseFloat(s.replace(/\./g, '').replace(',', '.'));
    }

    if (hasComma) {
        return parseFloat((s.match(/,/g) || []).length > 1 ? s.replace(/,/g, '') : s.replace(',', '.'));
    }

    if (hasDot && (s.match(/\./g) || []).length > 1) {
        return parseFloat(s.replace(/\./g, ''));
    }

    return parseFloat(s);
}

// Convención única de visualización: es-CO ('.' miles, ',' decimal).
function formatMoney(value) {
    if (value === null || value === undefined || isNaN(value)) return '';
    return Number(value).toLocaleString('es-CO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
}

function formatQty(value, decimals = 3) {
    if (value === null || value === undefined || isNaN(value)) return '';
    return Number(value).toLocaleString('es-CO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

window.MoneyFormat = { parseMoney, parseQty, formatMoney, formatQty };

export { parseMoney, parseQty, formatMoney, formatQty };
