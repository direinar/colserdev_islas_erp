// Aviso al salir con información digitada sin guardar, en todos los formularios que guardan
// datos (method="post"). Los filtros y búsquedas (GET) no avisan, ni los formularios de
// Livewire (wire:submit, no recargan la página) ni los marcados con data-sin-aviso.
//
// Solo cuentan los cambios que digita el usuario (event.isTrusted): galones.js y otros
// scripts disparan 'input' al recalcular y no deben marcar la página como modificada.
// Al guardar el formulario deja de avisar, también cuando se envía desde código con
// form.submit() (la planilla de turnos guarda así después de sus validaciones).

const conCambios = new Set();

const vigilado = (form) =>
    form instanceof HTMLFormElement &&
    (form.getAttribute('method') || '').toLowerCase() === 'post' &&
    !form.hasAttribute('data-sin-aviso') &&
    !form.hasAttribute('wire:submit') &&
    !form.hasAttribute('wire:submit.prevent');

const formularioDe = (campo) => campo?.form ?? campo?.closest?.('form') ?? null;

function marcar(event) {
    if (!event.isTrusted) return;

    const form = formularioDe(event.target);
    if (form && vigilado(form) && event.target.type !== 'hidden') {
        conCambios.add(form);
    }
}

document.addEventListener('input', marcar, true);
document.addEventListener('change', marcar, true);

// Burbuja en document: corre después de las validaciones de cada formulario; si alguna
// canceló el envío, lo digitado sigue sin guardar.
document.addEventListener('submit', (event) => {
    if (!event.defaultPrevented) {
        conCambios.delete(event.target);
    }
});

const submitOriginal = HTMLFormElement.prototype.submit;
HTMLFormElement.prototype.submit = function () {
    conCambios.delete(this);
    return submitOriginal.call(this);
};

const hayCambios = (form = null) =>
    form ? conCambios.has(form) : [...conCambios].some((f) => f.isConnected);

window.addEventListener('beforeunload', (event) => {
    if (hayCambios()) {
        event.preventDefault();
        event.returnValue = '';
    }
});

// Navegación de Livewire (wire:navigate) no dispara beforeunload.
document.addEventListener('livewire:navigate', (event) => {
    if (hayCambios() && !confirm('Hay información digitada que NO se ha guardado y se perderá. ¿Desea salir de todos modos?')) {
        event.preventDefault();
        return;
    }
    conCambios.clear();
});

// Para pantallas que piden su propia confirmación antes de descartar lo digitado.
window.avisoSinGuardar = {
    hayCambios,
    descartar: () => conCambios.clear(),
};
