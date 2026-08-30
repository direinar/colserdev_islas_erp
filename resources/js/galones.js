// Inicializa formateadores y listeners para la planilla
function initGalones() {
    // Evitamos errores si el DOM no está listo
    if (!document.body) return;

    // Bloquea letras/símbolos en los campos de valores (galones, lecturas,
    // valores monetarios): solo dígitos, punto y coma. Delegado en document
    // para cubrir también filas agregadas dinámicamente por JS.
    if (!document.body.dataset.numericGuardInit) {
        document.body.dataset.numericGuardInit = '1';
        const isValueField = el => el.matches?.(
            'input[inputmode="decimal"], .galones-input, .valor-total, .lectura-inicial, .lectura-final'
        );

        document.addEventListener('input', function (e) {
            const el = e.target;
            if (!isValueField(el)) return;
            const cleaned = el.value.replace(/[^0-9.,]/g, '');
            if (cleaned !== el.value) el.value = cleaned;
        });

        document.addEventListener('keypress', function (e) {
            if (!isValueField(e.target)) return;
            if (e.key.length === 1 && !/[0-9.,]/.test(e.key)) {
                e.preventDefault();
            }
        });

        document.addEventListener('paste', function (e) {
            if (!isValueField(e.target)) return;
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (/[^0-9.,]/.test(text)) {
                e.preventDefault();
                document.execCommand('insertText', false, text.replace(/[^0-9.,]/g, ''));
            }
        });
    }

    const parseGalones = value => {
        if (value === null || value === undefined) return NaN;
        let s = String(value).trim();
        if (s === '') return NaN;
        s = s.replace(/\s+/g, '');

        const hasDot = s.indexOf('.') !== -1;
        const hasComma = s.indexOf(',') !== -1;

        if (hasDot && hasComma) {
            const lastDot = s.lastIndexOf('.');
            const lastComma = s.lastIndexOf(',');
            if (lastDot > lastComma) {
                s = s.replace(/,/g, '');
                return parseFloat(s);
            } else {
                s = s.replace(/\./g, '').replace(/,/g, '.');
                return parseFloat(s);
            }
        }

        if (hasComma && !hasDot) {
            if ((s.match(/,/g) || []).length > 1) {
                s = s.replace(/,/g, '');
                return parseFloat(s);
            }
            s = s.replace(/,/g, '.');
            return parseFloat(s);
        }

        if (hasDot && !hasComma) {
            if ((s.match(/\./g) || []).length > 1) {
                s = s.replace(/\./g, '');
                return parseFloat(s);
            }
            return parseFloat(s);
        }

        return parseFloat(s);
    };

    const formatGalones = number => {
        if (isNaN(number)) return '';
        // Formato punto-decimal/coma-miles, igual al que Blade usa para
        // renderizar galones guardados (number_format($v, 3, '.', ',')).
        // Antes esta función usaba locale es-CO (coma decimal), lo que hacía
        // que el valor recién tecleado se viera distinto al que se muestra
        // al consultar el turno, y arriesgaba una mala interpretación al guardar.
        return number.toLocaleString('en-US', {
            minimumFractionDigits: 3,
            maximumFractionDigits: 3
        });
    };

    const formatMoney = number => {
        if (isNaN(number)) return '';
        return number.toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    };

    const formatMoneyWithDecimals = (number, decimals = 3) => {
        if (isNaN(number)) return '';
        return number.toLocaleString('es-CO', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    };

    const parseCurrency = value => {
        if (value === null || value === undefined) return NaN;
        let s = String(value).trim();
        if (s === '') return NaN;
        s = s.replace(/\s+/g, '');

        const hasDot = s.indexOf('.') !== -1;
        const hasComma = s.indexOf(',') !== -1;

        if (hasDot && hasComma) {
            const lastDot = s.lastIndexOf('.');
            const lastComma = s.lastIndexOf(',');
            if (lastDot > lastComma) {
                s = s.replace(/,/g, '');
                return parseFloat(s);
            } else {
                s = s.replace(/\./g, '').replace(/,/g, '.');
                return parseFloat(s);
            }
        }

        if (hasComma && !hasDot) {
            if ((s.match(/,/g) || []).length > 1) {
                s = s.replace(/,/g, '');
                return parseFloat(s);
            }
            s = s.replace(/,/g, '.');
            return parseFloat(s);
        }

        if (hasDot && !hasComma) {
            const parts = s.split('.');
            const lastPart = parts[parts.length - 1];
            const isThousandsGrouping = parts.length > 1 && parts.every(part => /^\d+$/.test(part)) && lastPart.length === 3;

            if (isThousandsGrouping) {
                s = s.replace(/\./g, '');
                return parseFloat(s);
            }

            return parseFloat(s);
        }

        return parseFloat(s);
    };

    // Los campos VALOR siempre son enteros (0 decimales): '.' y ',' solo son
    // separadores de miles, nunca decimales, así que se pueden eliminar sin ambigüedad.
    const parseMoneyInteger = value => {
        if (value === null || value === undefined) return NaN;
        const s = String(value).trim().replace(/[.,\s]/g, '');
        if (s === '') return NaN;
        return parseFloat(s);
    };

    const updateGrandTotal = () => {
        const totalField = document.querySelector('.ventas-total-turno');
        if (!totalField) return;
        let sum = 0;
        document.querySelectorAll('.valor-total').forEach(input => {
            const parsed = parseMoneyInteger(input.value);
            if (!isNaN(parsed)) sum += parsed;
        });
        totalField.textContent = formatMoney(sum);
    };

    const updateTirillasTotals = () => {
        const totalCorriente = Array.from(document.querySelectorAll('.galones-cte'))
            .reduce((sum, input) => {
                const value = parseGalones(input.value);
                return sum + (isNaN(value) ? 0 : value);
            }, 0);

        const totalAcpms = Array.from(document.querySelectorAll('.galones-acpm'))
            .reduce((sum, input) => {
                const value = parseGalones(input.value);
                return sum + (isNaN(value) ? 0 : value);
            }, 0);

        const corrienteInput = document.querySelector('.tirillas-galones-corriente');
        const acpmInput = document.querySelector('.tirillas-galones-acpm');
        const valorCorrienteInput = document.querySelector('.tirillas-valor-corriente');
        const valorAcpmsInput = document.querySelector('.tirillas-valor-acpm');

        if (corrienteInput) corrienteInput.value = formatGalones(totalCorriente);
        if (acpmInput) acpmInput.value = formatGalones(totalAcpms);

        if (valorCorrienteInput) {
            const precioCorriente = parseFloat(valorCorrienteInput.dataset.precio);
            const totalValorCorriente = totalCorriente * (isNaN(precioCorriente) ? 0 : precioCorriente);
            valorCorrienteInput.value = formatMoney(totalValorCorriente);
        }

        if (valorAcpmsInput) {
            const precioAcpms = parseFloat(valorAcpmsInput.dataset.precio);
            const totalValorAcpms = totalAcpms * (isNaN(precioAcpms) ? 0 : precioAcpms);
            valorAcpmsInput.value = formatMoney(totalValorAcpms);
        }
    };

    const updateVentasSegunLecturas = () => {
        const glsCorriente = Array.from(document.querySelectorAll('tr[data-combustible="corriente"] .lectura-gls'))
            .reduce((sum, input) => {
                const value = parseGalones(input.value);
                return sum + (isNaN(value) ? 0 : value);
            }, 0);

        const glsAcpms = Array.from(document.querySelectorAll('tr[data-combustible="acpm"] .lectura-gls'))
            .reduce((sum, input) => {
                const value = parseGalones(input.value);
                return sum + (isNaN(value) ? 0 : value);
            }, 0);

        const galonesCorrienteInput = document.querySelector('.ventas-lectura-galones-corriente');
        const galonesAcpmsInput = document.querySelector('.ventas-lectura-galones-acpm');
        const valorCorrienteInput = document.querySelector('.ventas-lectura-valor-corriente');
        const valorAcpmsInput = document.querySelector('.ventas-lectura-valor-acpm');
        const ventasTotal = document.querySelector('.ventas-total-lectura');

        if (galonesCorrienteInput) galonesCorrienteInput.value = formatGalones(glsCorriente);
        if (galonesAcpmsInput) galonesAcpmsInput.value = formatGalones(glsAcpms);

        if (valorCorrienteInput) {
            const precioCorriente = parseFloat(valorCorrienteInput.dataset.precio);
            const total = glsCorriente * (isNaN(precioCorriente) ? 0 : precioCorriente);
            valorCorrienteInput.value = formatMoney(total);
        }

        if (valorAcpmsInput) {
            const precioAcpms = parseFloat(valorAcpmsInput.dataset.precio);
            const total = glsAcpms * (isNaN(precioAcpms) ? 0 : precioAcpms);
            valorAcpmsInput.value = formatMoney(total);
        }

        if (ventasTotal) {
            const valorCorriente = valorCorrienteInput ? parseCurrency(valorCorrienteInput.value) : 0;
            const valorAcpms = valorAcpmsInput ? parseCurrency(valorAcpmsInput.value) : 0;
            const sum = (isNaN(valorCorriente) ? 0 : valorCorriente) + (isNaN(valorAcpms) ? 0 : valorAcpms);
            ventasTotal.textContent = formatMoney(sum);
        }
    };

    // Inputs de galones
    document.querySelectorAll('.galones-input').forEach(input => {
        if (input.dataset.galonesInit) return;
        input.dataset.galonesInit = '1';

        const formatValue = function () {
            const galones = parseGalones(this.value);
            this.value = formatGalones(galones);
            updateGrandTotal();
            updateTirillasTotals();
        };

        input.addEventListener('blur', formatValue);
        input.addEventListener('change', formatValue);
        input.addEventListener('focusout', formatValue);

        input.addEventListener('input', function () {
            const precio = parseFloat(this.dataset.precio);
            const galones = parseGalones(this.value);

            if (isNaN(galones) || isNaN(precio)) {
                const totalInput = this.closest('tr')?.querySelector('.valor-total');
                if (totalInput) totalInput.value = '';
                updateGrandTotal();
                updateTirillasTotals();
                return;
            }

            const total = galones * precio;
            const totalInput = this.closest('tr')?.querySelector('.valor-total');
            if (totalInput) totalInput.value = formatMoney(total);
            updateGrandTotal();
            updateTirillasTotals();
        });
    });

    // Lecturas: formato y cálculo GLS por fila
    const updateRowGls = (row) => {
        const inicialInput = row.querySelector('.lectura-inicial');
        const finalInput = row.querySelector('.lectura-final');
        const glsInput = row.querySelector('.lectura-gls');

        const inicial = inicialInput ? parseGalones(inicialInput.value) : NaN;
        const final = finalInput ? parseGalones(finalInput.value) : NaN;

        if (!isNaN(inicial) && !isNaN(final)) {
            const diff = final - inicial;
            if (glsInput) glsInput.value = formatGalones(diff);
        } else {
            if (glsInput) glsInput.value = '';
        }

        updateVentasSegunLecturas();
    };

    // Añadir listeners a filas que contienen lecturas
    document.querySelectorAll('tbody tr').forEach(row => {
        if (row.dataset.galonesRowInit) return;
        row.dataset.galonesRowInit = '1';

        const inicial = row.querySelector('.lectura-inicial');
        const final = row.querySelector('.lectura-final');

        if (inicial || final) {
            const handler = function () {
                updateRowGls(row);
            };

            if (inicial) {
                inicial.addEventListener('input', handler);
                inicial.addEventListener('blur', function () {
                    const v = parseGalones(this.value);
                    this.value = formatGalones(v);
                    updateRowGls(row);
                });
            }

            if (final) {
                final.addEventListener('input', handler);
                final.addEventListener('blur', function () {
                    const v = parseGalones(this.value);
                    this.value = formatGalones(v);
                    updateRowGls(row);
                });
            }
        }
    });

    // Inicializar valores al cargar
    document.querySelectorAll('tbody tr').forEach(row => updateRowGls(row));

    updateGrandTotal();
    updateTirillasTotals();
    updateVentasSegunLecturas();
}

document.addEventListener('DOMContentLoaded', initGalones);
document.addEventListener('livewire:load', initGalones);
document.addEventListener('livewire:updated', initGalones);

// Generic decimal formatter for inputs used across the planilla (money, tc, transferencias, etc.)
function initDecimalFormatters() {
    function parseNumber(value) {
        if (!value) return NaN;
        return parseFloat(String(value).replace(/\./g, '').replace(',', '.'));
    }

    function formatNumber(value, decimals) {
        if (isNaN(value)) return '';
        return value.toLocaleString('es-CO', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    document.querySelectorAll('input[inputmode="decimal"]').forEach(input => {
        if (input.dataset.decInit) return;
        input.dataset.decInit = '1';

        const decimalsAttr = input.dataset.decimals;
        const decimals = typeof decimalsAttr !== 'undefined' ? parseInt(decimalsAttr, 10) : (/(galon|gls|lectura|galones|galon)/i.test(input.name || input.className) ? 3 : 0);

        input.addEventListener('blur', function () {
            const v = parseNumber(this.value);
            if (!isNaN(v)) {
                this.value = formatNumber(v, decimals);
            }
        });

        // sanitize paste
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text') || '';
            const clean = paste.replace(/[^\d.,-]/g, '');
            document.execCommand('insertText', false, clean);
        });
    });
}

document.addEventListener('DOMContentLoaded', initDecimalFormatters);
document.addEventListener('livewire:load', initDecimalFormatters);
document.addEventListener('livewire:updated', initDecimalFormatters);
