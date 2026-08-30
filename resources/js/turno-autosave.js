// Autoguardado local de la Planilla de Turnos: protege el trabajo en curso
// ante un apagón, cierre accidental o falla del equipo. Guarda un borrador en
// localStorage mientras se escribe y lo ofrece recuperar al volver a abrir el
// mismo turno; se descarta automáticamente al enviar el formulario con éxito.
function initTurnoAutosave() {
    const form = document.getElementById('turno-form');
    if (!form) return;
    if (form.dataset.autosaveInit) return;
    form.dataset.autosaveInit = '1';

    // Tablas cuyas filas se agregan dinámicamente por JS: hay que recrearlas
    // antes de poder asignar los valores guardados en el borrador.
    const DYNAMIC_TABLES = [
        { prefix: 'recaudos', tbody: '#recaudos-body', addBtn: '#add-recaudo-row' },
        { prefix: 'recaudos_admin', tbody: '#recaudos-admin-body', addBtn: '#add-recaudo-admin-row' },
        { prefix: 'medios_pago', tbody: '#medios-pago-body', addBtn: '#add-medio-pago-row' },
        { prefix: 'qr_pagos', tbody: '#qr-body', addBtn: '#add-qr-row' },
        { prefix: 'transferencias', tbody: '#transferencias-body', addBtn: '#add-transferencia-row' },
        { prefix: 'gasolina_eds', tbody: '#gasolina-eds-body', addBtn: '#add-gasolina-row' },
        { prefix: 'varios', tbody: '#varios-body', addBtn: '#add-varios-row' },
        { prefix: 'urea_lubricantes', tbody: '.lubricantes-table tbody', addBtn: '#add-lubricante-row' },
    ];

    const storageKey = () => {
        const fecha = form.querySelector('input[name="fecha"]')?.value || 'sin-fecha';
        const numero = form.querySelector('input[name="numero_turno"]')?.value || 'sin-numero';
        return `turno-draft:${fecha}:${numero}`;
    };

    const serializeForm = () => {
        const entries = [];
        new FormData(form).forEach((value, name) => {
            if (name === '_token') return;
            entries.push([name, value]);
        });
        return entries;
    };

    let saveTimer = null;
    const scheduleSave = () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            try {
                localStorage.setItem(storageKey(), JSON.stringify({
                    savedAt: Date.now(),
                    entries: serializeForm(),
                }));
            } catch {
                // localStorage lleno o no disponible (modo privado): no es crítico, se ignora.
            }
        }, 800);
    };

    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);
    // Respaldo periódico por si algún cambio no dispara 'input'/'change'.
    setInterval(scheduleSave, 20000);

    form.addEventListener('submit', () => {
        localStorage.removeItem(storageKey());
    });

    const ensureRowCount = (table, neededCount) => {
        const tbody = document.querySelector(table.tbody);
        const addBtn = document.querySelector(table.addBtn);
        if (!tbody || !addBtn) return;

        let current = tbody.querySelectorAll('tr[data-index]').length;
        while (current < neededCount) {
            addBtn.click();
            current++;
        }
    };

    const restoreDraft = raw => {
        let draft;
        try {
            draft = JSON.parse(raw);
        } catch {
            return;
        }
        if (!draft?.entries?.length) return;

        // Crear de antemano las filas dinámicas necesarias según el índice
        // más alto presente en el borrador para cada tabla.
        DYNAMIC_TABLES.forEach(table => {
            const regex = new RegExp(`^${table.prefix}\\[(\\d+)\\]`);
            const maxIndex = draft.entries.reduce((max, [name]) => {
                const match = name.match(regex);
                return match ? Math.max(max, parseInt(match[1], 10)) : max;
            }, -1);
            if (maxIndex >= 0) ensureRowCount(table, maxIndex + 1);
        });

        draft.entries.forEach(([name, value]) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (!field) return;
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
            field.dispatchEvent(new Event('blur', { bubbles: true }));
        });
    };

    const raw = localStorage.getItem(storageKey());
    if (raw) {
        const savedAt = JSON.parse(raw)?.savedAt;
        const minutesAgo = savedAt ? Math.round((Date.now() - savedAt) / 60000) : null;
        const when = minutesAgo !== null ? ` (hace ${minutesAgo} min)` : '';
        if (confirm(`Se encontró un borrador sin guardar de este turno${when}. ¿Deseas recuperarlo?`)) {
            restoreDraft(raw);
        } else {
            localStorage.removeItem(storageKey());
        }
    }
}

document.addEventListener('DOMContentLoaded', initTurnoAutosave);
