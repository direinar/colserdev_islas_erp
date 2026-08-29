<x-erp-card title="SOBRANTES Y FALTANTES">

    <div class="table-responsive">
        <table class="table table-bordered table-sm" style="background-color: #f7f2e7; border-color: #b8a18f;">
            <tbody>
                <tr>
                    <td><strong>SOBRANTE o FALTANTE SEGUN CIERRES DE IAPROPIADA</strong></td>
                    <td class="text-end fw-bold text-danger" id="sobrante-cierre-iapropiada">0</td>
                </tr>
                <tr>
                    <td><strong>SOBRANTE o FALTANTE x LECTURA SURTIDORES</strong></td>
                    <td class="text-end fw-bold text-danger" id="sobrante-lectura-surtidores">0</td>
                </tr>
                <tr>
                    <td><strong>TRASLADO A SOBRANTE</strong></td>
                    <td class="text-end fw-bold" id="traslado-sobrante">0</td>
                </tr>
                <tr>
                    <td><strong>TRASLADO A FALTANTE</strong></td>
                    <td class="text-end fw-bold" id="traslado-faltante">0</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="row g-2 mt-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">NOMBRE DEL VENDEDOR:</label>
            <input type="text" class="form-control form-control-sm" name="nombre_vendedor" id="nombre-vendedor"
                value="{{ old('nombre_vendedor', optional($turno ?? null)->nombre_vendedor ?? auth()->user()->name) }}"
                readonly />
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold d-block">REVISADO:</label>
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
                <form method="POST" action="{{ route('turnos.revisar', $turnoActual) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success">Marcar como revisado</button>
                </form>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sobranteCell = document.getElementById('sobrante-cierre-iapropiada');
            const sobranteSurtidoresCell = document.getElementById('sobrante-lectura-surtidores');
            const totalRecibidoCell = document.getElementById('resumen-total');
            const totalVentasIapropiadaCell = document.getElementById('resumen-total-iapropiada');
            const totalSurtidoresCell = document.getElementById('resumen-total-surtidores');

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
                const totalRecibido = totalRecibidoCell ? parseNumber(totalRecibidoCell.textContent) : 0;
                const totalVentasIapropiada = totalVentasIapropiadaCell ? parseNumber(totalVentasIapropiadaCell
                    .textContent) : 0;
                const totalSurtidores = totalSurtidoresCell ? parseNumber(totalSurtidoresCell.textContent) : 0;

                const sobranteIapropiada = totalRecibido - totalVentasIapropiada;
                const sobranteSurtidores = totalRecibido - totalSurtidores;

                if (sobranteCell) {
                    sobranteCell.textContent = formatNumber(sobranteIapropiada);
                    sobranteCell.classList.toggle('text-danger', sobranteIapropiada < 0);
                }

                if (sobranteSurtidoresCell) {
                    sobranteSurtidoresCell.textContent = formatNumber(sobranteSurtidores);
                    sobranteSurtidoresCell.classList.toggle('text-danger', sobranteSurtidores < 0);
                }
            }

            const observer = new MutationObserver(updateSobrante);

            [totalRecibidoCell, totalVentasIapropiadaCell, totalSurtidoresCell].forEach(element => {
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
        });
    </script>

</x-erp-card>
