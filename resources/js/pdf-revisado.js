// PDF de la planilla al marcarla como revisada: queda en la carpeta del equipo que el
// administrador eligió una vez (botón "Carpeta de planillas"), dentro de una subcarpeta con el
// número de la planilla: <carpeta>\Planilla 253\Planilla 253.pdf.
//
// Usa la API de acceso a archivos de Chrome/Edge. El navegador recuerda la carpeta (IndexedDB),
// pero puede pedir confirmar el permiso de nuevo: entonces se ofrece un botón "Guardar PDF".
// En navegadores sin esa API el PDF se descarga normalmente (carpeta Descargas).

const BD = 'erp-planillas';
const ALMACEN = 'carpetas';
const CLAVE = 'planillas';
const soportado = typeof window.showDirectoryPicker === 'function';

function abrirBd() {
    return new Promise((resolver, rechazar) => {
        const solicitud = indexedDB.open(BD, 1);
        solicitud.onupgradeneeded = () => solicitud.result.createObjectStore(ALMACEN);
        solicitud.onsuccess = () => resolver(solicitud.result);
        solicitud.onerror = () => rechazar(solicitud.error);
    });
}

async function leerCarpeta() {
    try {
        const bd = await abrirBd();
        return await new Promise((resolver) => {
            const lectura = bd.transaction(ALMACEN).objectStore(ALMACEN).get(CLAVE);
            lectura.onsuccess = () => resolver(lectura.result ?? null);
            lectura.onerror = () => resolver(null);
        });
    } catch {
        return null;
    }
}

async function guardarCarpeta(carpeta) {
    const bd = await abrirBd();
    await new Promise((resolver, rechazar) => {
        const tx = bd.transaction(ALMACEN, 'readwrite');
        tx.objectStore(ALMACEN).put(carpeta, CLAVE);
        tx.oncomplete = resolver;
        tx.onerror = () => rechazar(tx.error);
    });
}

async function elegirCarpeta() {
    const carpeta = await window.showDirectoryPicker({ id: 'planillas', mode: 'readwrite' });
    await guardarCarpeta(carpeta);
    mostrarNombre(carpeta);
    return carpeta;
}

function mostrarNombre(carpeta) {
    document.querySelectorAll('[data-carpeta-planillas-nombre]').forEach((etiqueta) => {
        etiqueta.textContent = carpeta ? `Planillas en: ${carpeta.name}` : 'Carpeta de planillas';
    });
}

async function permiso(carpeta, pedir) {
    const opciones = { mode: 'readwrite' };
    if ((await carpeta.queryPermission(opciones)) === 'granted') return true;
    return pedir && (await carpeta.requestPermission(opciones)) === 'granted';
}

async function descargarPdf(url) {
    const respuesta = await fetch(url, { credentials: 'same-origin' });
    if (!respuesta.ok) throw new Error(`No se pudo generar el PDF (${respuesta.status}).`);
    return respuesta.blob();
}

async function escribir(carpeta, numero, pdf) {
    const nombre = `Planilla ${numero}`;
    const subcarpeta = await carpeta.getDirectoryHandle(nombre, { create: true });
    const archivo = await subcarpeta.getFileHandle(`${nombre}.pdf`, { create: true });
    const escritura = await archivo.createWritable();
    await escritura.write(pdf);
    await escritura.close();
    return `${carpeta.name}\\${nombre}\\${nombre}.pdf`;
}

function descargaNormal(url) {
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = '';
    document.body.appendChild(enlace);
    enlace.click();
    enlace.remove();
}

function avisar(titulo, texto, icono = 'success') {
    window.Swal?.fire({ title: titulo, text: texto, icon: icono, timer: icono === 'success' ? 4000 : undefined });
}

/**
 * Guarda el PDF; con conGesto (clic del usuario) puede elegir carpeta o renovar el permiso.
 */
async function guardarPdf(url, numero, conGesto) {
    let carpeta = await leerCarpeta();

    if (!carpeta || !(await permiso(carpeta, conGesto))) {
        if (!conGesto) return false;
        if (!carpeta) carpeta = await elegirCarpeta();
        if (!(await permiso(carpeta, true))) return false;
    }

    const ruta = await escribir(carpeta, numero, await descargarPdf(url));
    avisar(`PDF de la planilla ${numero} guardado`, ruta);
    return true;
}

function ofrecerBoton(url, numero) {
    window.Swal?.fire({
        title: `Guardar el PDF de la planilla ${numero}`,
        text: 'El navegador pide confirmar el acceso a la carpeta de planillas.',
        icon: 'info',
        confirmButtonText: 'Guardar PDF',
        showCancelButton: true,
        cancelButtonText: 'Ahora no',
    }).then((resultado) => {
        if (resultado.isConfirmed) {
            guardarPdf(url, numero, true).catch((error) => {
                if (error?.name !== 'AbortError') avisar('No se guardó el PDF', error.message, 'error');
            });
        }
    });
}

async function alCargar() {
    const pendiente = document.getElementById('pdf-revisado');
    const botones = document.querySelectorAll('[data-carpeta-planillas]');

    if (!soportado) {
        botones.forEach((boton) => boton.classList.add('d-none'));
    } else {
        mostrarNombre(await leerCarpeta());
        botones.forEach((boton) => boton.addEventListener('click', () => {
            elegirCarpeta().then((carpeta) => avisar('Carpeta de planillas', `Los PDF se guardarán en: ${carpeta.name}`))
                .catch((error) => {
                    if (error?.name !== 'AbortError') avisar('No se eligió la carpeta', error.message, 'error');
                });
        }));
    }

    if (!pendiente) return;
    const { url, numero } = pendiente.dataset;

    if (!soportado) {
        descargaNormal(url);
        return;
    }

    try {
        if (!(await guardarPdf(url, numero, false))) ofrecerBoton(url, numero);
    } catch (error) {
        avisar('No se guardó el PDF', error.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', alCargar);
