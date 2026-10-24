<x-erp-card title="SOBRANTES Y FALTANTES" title-class="lectura-electronica-title">

    <div class="table-responsive">
        <table class="table table-bordered table-sm" style="background-color: #f7f2e7; border-color: #b8a18f;">
            <tbody>
                <tr>
                    <td><strong>SOBRANTE o FALTANTE SEGUN CIERRES DE IAPROPIADA</strong></td>
                    <td class="text-end fw-bold text-danger" id="sobrante-cierre-iapropiada">0</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap align-items-end gap-2 mt-2">
        <div class="flex-grow-1" style="min-width: min(100%, 260px);">
            <label class="form-label fw-bold" for="observaciones">OBSERVACIONES</label>
            <textarea class="form-control form-control-sm" name="observaciones" id="observaciones" rows="2">{{ old('observaciones', optional($turno ?? null)->observaciones) }}</textarea>
        </div>
        <button type="button" id="trasladar-sobrante-btn" class="btn btn-sm btn-primary">
            TRASLADAR
        </button>
        @if (auth()->user()->isAdministrador())
            {{-- Solo el administrador reversa el traslado; el cambio se aplica al Guardar. --}}
            <button type="button" id="reversar-traslado-btn" class="btn btn-sm btn-outline-danger">
                REVERSAR
            </button>
        @endif
        {{-- Persisten el traslado para que el resumen no lo pierda al guardar. Se envían
             como entero sin separadores para que no se alteren al volver a guardar. --}}
        <input type="hidden" name="traslado_sobrante" id="traslado-sobrante-input"
            value="{{ (int) round((float) (optional($turno ?? null)->traslado_sobrante ?? 0)) }}">
        <input type="hidden" name="traslado_faltante" id="traslado-faltante-input"
            value="{{ (int) round((float) (optional($turno ?? null)->traslado_faltante ?? 0)) }}">
        {{-- Sobrante/faltante sin trasladar: si no es cero la planilla no se puede marcar como revisada. --}}
        <input type="hidden" name="sobrante_pendiente" id="sobrante-pendiente-input" value="">
    </div>

    <div class="row g-2 mt-3">
        <div class="col-md-6">
            <label class="form-label fw-bold" for="nombre-vendedor">NOMBRE DEL VENDEDOR:</label>
            {{-- Quien registró la planilla; si otro usuario la corrige, el vendedor no cambia. --}}
            <input type="text" class="form-control form-control-sm" name="nombre_vendedor" id="nombre-vendedor"
                value="{{ optional($turno ?? null)->nombre_vendedor ?: auth()->user()->name }}" readonly />
        </div>
        <div class="col-md-6">
            {{-- No es un campo sino el estado de la planilla: por eso no lleva etiqueta de formulario. --}}
            <div class="form-label fw-bold d-block">REVISADO:</div>
            @php
                $turnoActual = $turno ?? null;
                $estaRevisado = (bool) optional($turnoActual)->revisado;
            @endphp
            @if ($estaRevisado)
                <span class="badge bg-success">
                    REVISADO por {{ $turnoActual->revisado_por }}
                    @if ($turnoActual->revisado_at)
                        el {{ $turnoActual->revisado_at->format('d/m/Y H:i') }}
                    @endif
                </span>
            @else
                <span class="badge bg-danger">PENDIENTE DE REVISIÓN</span>
            @endif

            @if ($turnoActual && !$estaRevisado && auth()->user()->isAdministrador())
                {{-- Nota: no se anida un <form> aquí; este botón envía #form-marcar-revisado,
                     definido fuera del formulario principal de la planilla (ver create.blade.php),
                     para evitar que el navegador cierre prematuramente ese formulario. --}}
                <button type="submit" form="form-marcar-revisado" id="marcar-revisado-btn"
                    class="btn btn-sm btn-outline-success mt-2">
                    Marcar como revisado
                </button>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sobranteCell = document.getElementById('sobrante-cierre-iapropiada');
            const totalRecibidoCell = document.getElementById('resumen-total');
            const totalVentasIapropiadaCell = document.getElementById('resumen-total-iapropiada');
            const trasladarButton = document.getElementById('trasladar-sobrante-btn');
            const trasladoSobranteInput = document.getElementById('traslado-sobrante-input');
            const trasladoFaltanteInput = document.getElementById('traslado-faltante-input');
            const sobrantePendienteInput = document.getElementById('sobrante-pendiente-input');
            const marcarRevisadoButton = document.getElementById('marcar-revisado-btn');

            // Restaura el traslado guardado en el turno para que el resumen no
            // vuelva a mostrar el sobrante/faltante original al recargar la página.
            const trasladoSobrantePrevio = Number(trasladoSobranteInput?.value || 0);
            const trasladoFaltantePrevio = Number(trasladoFaltanteInput?.value || 0);
            let trasladoAplicado = trasladoSobrantePrevio !== 0 || trasladoFaltantePrevio !== 0;

            if (trasladoAplicado) {
                window.turnoTraslado = {
                    sobrante: trasladoSobrantePrevio,
                    faltante: trasladoFaltantePrevio
                };
            }

            function parseNumber(value) {
                if (!value) return 0;
                return Number(String(value).replace(/\./g, '').replace(/,/g, '.')) || 0;
            }

            function formatNumber(value) {
                return Number(value).toLocaleString('es-CO', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function updateSobrante() {
                const totalRecibido = window.turnoResumenTotalBase ?? (totalRecibidoCell ? parseNumber(
                    totalRecibidoCell.textContent) : 0);
                const totalVentasIapropiada = totalVentasIapropiadaCell ? parseNumber(totalVentasIapropiadaCell
                    .textContent) : 0;

                // El total recibido ya incluye el traslado aplicado: si después se modifica la
                // planilla, la nueva diferencia vuelve a aparecer en vez de quedar congelada en 0.
                const sobranteIapropiada = totalRecibido - totalVentasIapropiada;

                if (sobranteCell) {
                    sobranteCell.textContent = formatNumber(sobranteIapropiada);
                    sobranteCell.classList.toggle('text-danger', sobranteIapropiada < 0);
                }

                if (sobrantePendienteInput) {
                    sobrantePendienteInput.value = String(Math.round(sobranteIapropiada));
                }

                if (trasladarButton) {
                    trasladarButton.disabled = sobranteIapropiada === 0 ||
                        trasladarButton.dataset.transferredValue === String(sobranteIapropiada);
                    trasladarButton.dataset.currentValue = String(sobranteIapropiada);
                }
            }

            const reversarButton = document.getElementById('reversar-traslado-btn');

            function refreshReversar() {
                if (reversarButton) reversarButton.disabled = !trasladoAplicado;
            }

            // Devuelve el sobrante/faltante a su estado original (sin traslado).
            reversarButton?.addEventListener('click', function() {
                if (!trasladoAplicado) return;
                if (!confirm('¿Reversar el traslado? El sobrante/faltante vuelve a su valor original. Recuerde Guardar.')) return;

                window.turnoTraslado = {
                    sobrante: 0,
                    faltante: 0
                };
                trasladoAplicado = false;
                if (trasladarButton) delete trasladarButton.dataset.transferredValue;
                if (trasladoSobranteInput) trasladoSobranteInput.value = '0';
                if (trasladoFaltanteInput) trasladoFaltanteInput.value = '0';
                document.dispatchEvent(new CustomEvent('turno:traslado-aplicado'));
                updateSobrante();
                refreshReversar();
            });

            trasladarButton?.addEventListener('click', function() {
                const value = Math.round(Number(this.dataset.currentValue || 0));
                if (!value) return;

                window.turnoTraslado = window.turnoTraslado || {
                    sobrante: 0,
                    faltante: 0
                };

                if (value < 0) {
                    window.turnoTraslado.faltante += Math.abs(value);
                } else {
                    window.turnoTraslado.sobrante -= value;
                }

                trasladoAplicado = true;
                this.dataset.transferredValue = String(value);
                if (trasladoSobranteInput) trasladoSobranteInput.value = String(window.turnoTraslado
                    .sobrante);
                if (trasladoFaltanteInput) trasladoFaltanteInput.value = String(window.turnoTraslado
                    .faltante);
                document.dispatchEvent(new CustomEvent('turno:traslado-aplicado'));
                updateSobrante();
                refreshReversar();
            });

            // No se marca como revisada una planilla con SOBRANTE o FALTANTE sin
            // trasladar, ni con un traslado hecho en pantalla que aún no se guardó.
            marcarRevisadoButton?.addEventListener('click', function(event) {
                const pendiente = Math.round(Number(sobrantePendienteInput?.value || 0));
                const trasladoSinGuardar =
                    Number(trasladoSobranteInput?.value || 0) !== trasladoSobrantePrevio ||
                    Number(trasladoFaltanteInput?.value || 0) !== trasladoFaltantePrevio;

                let mensaje = null;
                if (pendiente !== 0) {
                    mensaje = 'Hay un ' + (pendiente < 0 ? 'FALTANTE' : 'SOBRANTE') + ' de $' +
                        formatNumber(Math.abs(pendiente)) +
                        ' sin trasladar. Use el botón TRASLADAR y guarde la planilla antes de marcarla como revisada.';
                } else if (trasladoSinGuardar) {
                    mensaje = 'El traslado del sobrante/faltante no se ha guardado. Guarde la planilla antes de marcarla como revisada.';
                }

                if (mensaje) {
                    event.preventDefault();
                    Swal.fire({
                        title: 'No se puede revisar',
                        text: mensaje,
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                    });
                }
            });

            const observer = new MutationObserver(updateSobrante);

            [totalRecibidoCell, totalVentasIapropiadaCell].forEach(element => {
                if (element) {
                    observer.observe(element, {
                        characterData: true,
                        subtree: true,
                        childList: true
                    });
                }
            });

            // initial calculation
            updateSobrante();
            refreshReversar();
        });
    </script>

</x-erp-card>
