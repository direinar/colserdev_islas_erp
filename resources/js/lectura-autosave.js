// Autoguardado local de la Lectura electrónica: si el equipo se apaga o se cierra la página
// sin GUARDAR, lo digitado queda en este equipo (localStorage) y se ofrece recuperarlo al
// volver a abrir la misma fecha. Mientras haya lecturas sin guardar se muestra una barra
// grande con el botón GUARDAR. El borrador se borra cuando el servidor confirma el guardado.
function initLecturaAutosave() {
    const form = document.getElementById('lectura-electronica-form');
    if (!form || form.dataset.autosaveInit) return;
    form.dataset.autosaveInit = '1';

    const fecha = form.querySelector('input[name="fecha"]')?.value || 'sin-fecha';
    const clave = `lectura-draft:${fecha}`;
    const barra = document.getElementById('lectura-sin-guardar');
    const textoBarra = document.getElementById('lectura-sin-guardar-texto');
    const fechaLegible = fecha.split('-').reverse().join('/');

    const leer = () => {
        try {
            return JSON.parse(localStorage.getItem(clave) || 'null');
        } catch {
            return null;
        }
    };
    const borrar = () => {
        try {
            localStorage.removeItem(clave);
        } catch {
            // localStorage no disponible: no hay borrador que borrar.
        }
    };

    // El servidor confirmó el guardado de esta fecha: el borrador ya no hace falta.
    if (form.dataset.guardadoOk) {
        borrar();
    }

    // Campos que el usuario digita (las iniciales bloqueadas vienen del día anterior).
    const campos = () => Array.from(form.querySelectorAll('input.lectura-final, input.lectura-inicial:not([readonly])'));
    const valores = () => Object.fromEntries(campos().map((campo) => [campo.name, campo.value]));
    const originales = valores();
    const hayDiferencias = (datos) => Object.entries(datos).some(([nombre, valor]) => (originales[nombre] ?? '') !== valor);

    const mostrarBarra = (visible, hora = null) => {
        if (!barra) return;
        barra.hidden = !visible;
        document.body.classList.toggle('con-lectura-sin-guardar', visible);
        if (textoBarra && hora) {
            textoBarra.textContent = `Lecturas del ${fechaLegible} SIN GUARDAR en el sistema (copia en este equipo, ${hora}).`;
        }
    };

    let temporizador = null;
    const programarGuardado = () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => {
            const datos = valores();
            if (!hayDiferencias(datos)) {
                borrar();
                mostrarBarra(false);
                return;
            }
            try {
                localStorage.setItem(clave, JSON.stringify({ savedAt: Date.now(), valores: datos }));
            } catch {
                // localStorage lleno o no disponible (modo privado): queda solo el aviso.
            }
            mostrarBarra(true, new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }));
        }, 500);
    };

    // Solo cuentan los cambios del usuario o del botón COPIAR (galones.js también dispara 'input').
    form.addEventListener('input', (event) => {
        if (event.target.matches('input.lectura-final, input.lectura-inicial')) {
            programarGuardado();
        }
    });
    form.addEventListener('submit', () => mostrarBarra(false));

    const restaurar = (datos) => {
        Object.entries(datos).forEach(([nombre, valor]) => {
            const campo = form.querySelector(`[name="${CSS.escape(nombre)}"]`);
            if (!campo || campo.readOnly) return;
            campo.value = valor;
            // galones.js recalcula GLS y las ventas según lectura.
            campo.dispatchEvent(new Event('input', { bubbles: true }));
        });
        programarGuardado();
    };

    const borrador = leer();
    if (!borrador?.valores || !hayDiferencias(borrador.valores)) {
        if (borrador) borrar();
        return;
    }

    const minutos = borrador.savedAt ? Math.max(0, Math.round((Date.now() - borrador.savedAt) / 60000)) : null;
    const hace = minutos === null ? '' : minutos < 60 ? ` hace ${minutos} min` : ` hace ${Math.round(minutos / 60)} h`;
    const mensaje = `En este equipo quedaron lecturas del ${fechaLegible} digitadas${hace} que NO se guardaron en el sistema.`;

    if (window.Swal) {
        window.Swal.fire({
            title: 'Lecturas sin guardar',
            html: `<p style="font-size:1.1rem">${mensaje}</p><p>¿Desea recuperarlas? Después revise y pulse <strong>GUARDAR</strong>.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, recuperar',
            cancelButtonText: 'No, descartarlas',
            reverseButtons: true,
            allowOutsideClick: false,
        }).then((resultado) => {
            if (resultado.isConfirmed) {
                restaurar(borrador.valores);
            } else {
                borrar();
            }
        });
    } else if (confirm(`${mensaje} ¿Desea recuperarlas?`)) {
        restaurar(borrador.valores);
    } else {
        borrar();
    }
}

document.addEventListener('DOMContentLoaded', initLecturaAutosave);
