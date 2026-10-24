@props(['url', 'archivo', 'titulo'])

{{-- COMPARTIR: envía un PDF a WhatsApp (control de tanques, estado de cuenta de cartera). --}}
<button type="button" class="btn btn-outline-light btn-sm" id="compartir-pdf"
    data-url="{{ $url }}" data-archivo="{{ $archivo }}" data-titulo="{{ $titulo }}">
    <i class="bi bi-whatsapp" aria-hidden="true"></i> COMPARTIR
</button>

<script>
    // COMPARTIR envía el PDF a WhatsApp.
    // - Celular: menú de compartir del teléfono (WhatsApp → el grupo). El PDF se genera al abrir la
    //   página, porque el navegador solo deja abrir el menú justo después del toque.
    // - Computador: abre WhatsApp Web y descarga el PDF para adjuntarlo en el grupo.
    (function() {
        const boton = document.getElementById('compartir-pdf');
        if (!boton) return;

        const nombre = boton.dataset.archivo;
        // En el computador el menú de compartir es el de Windows/Mac, que no abre WhatsApp Web.
        const esCelular = navigator.userAgentData?.mobile ?? /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        const compartirArchivos = esCelular && !!(navigator.canShare
            && navigator.canShare({ files: [new File([''], nombre, { type: 'application/pdf' })] }));

        const generarPdf = () => fetch(boton.dataset.url, { credentials: 'same-origin' })
            .then(function(respuesta) {
                if (!respuesta.ok) throw new Error('No se pudo generar el PDF.');
                return respuesta.blob();
            })
            .then(blob => new File([blob], nombre, { type: 'application/pdf' }));

        let pdf = compartirArchivos ? generarPdf() : null;
        pdf?.catch(() => { pdf = null; });

        const errorAlCompartir = function(error) {
            // Cancelar el menú de compartir no es un error.
            if (error.name !== 'AbortError') {
                Swal.fire({ title: 'No se pudo compartir', text: error.message, icon: 'error', confirmButtonText: 'Entendido' });
            }
        };

        const compartir = archivo => navigator.share({ files: [archivo], title: boton.dataset.titulo });

        boton.addEventListener('click', async function() {
            if (!compartirArchivos) {
                // La ventana se abre antes de esperar el PDF para que el navegador no la bloquee.
                window.open('https://web.whatsapp.com/', 'whatsapp');
                boton.disabled = true;
                try {
                    const archivo = await generarPdf();
                    const enlace = document.createElement('a');
                    enlace.href = URL.createObjectURL(archivo);
                    enlace.download = archivo.name;
                    enlace.click();
                    setTimeout(() => URL.revokeObjectURL(enlace.href), 1000);
                    Swal.fire({
                        title: 'WhatsApp Web abierto',
                        text: 'El PDF se descargó: en WhatsApp Web abra el grupo, toque el clip (adjuntar) y elija el archivo ' + archivo.name + '.',
                        icon: 'info',
                        confirmButtonText: 'Entendido',
                    });
                } catch (error) {
                    errorAlCompartir(error);
                } finally {
                    boton.disabled = false;
                }
                return;
            }

            boton.disabled = true;
            try {
                const archivo = await (pdf ??= generarPdf());
                try {
                    await compartir(archivo);
                } catch (error) {
                    if (error.name !== 'NotAllowedError') throw error;
                    // El PDF tardó y el navegador ya no deja abrir el menú: se pide un segundo toque.
                    await Swal.fire({
                        title: 'PDF listo',
                        text: 'Toque COMPARTIR para enviarlo por WhatsApp.',
                        icon: 'success',
                        confirmButtonText: 'COMPARTIR',
                        showCancelButton: true,
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => compartir(archivo).catch(errorAlCompartir),
                    });
                }
            } catch (error) {
                errorAlCompartir(error);
            } finally {
                boton.disabled = false;
            }
        });
    })();
</script>
