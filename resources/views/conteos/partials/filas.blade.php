{{-- Tabla de filas texto/valor del conteo con + AGREGAR FILA y × (retiros, gastos, retiros de
     moneda). $columnas: campo => título de las columnas de texto; el valor va al final. --}}
@php
    $pesosInput = fn ($valor): string => (float) $valor != 0 ? number_format((float) $valor, 0, ',', '.') : '';
    $filasMostradas = $filas->isEmpty() ? collect([null]) : $filas->values();
@endphp

<div class="table-responsive">
    <table class="table table-bordered table-sm mb-0 conteo-tabla" data-filas-conteo>
        <thead>
            <tr>
                @foreach ($columnas as $titulo)
                    <th>{{ $titulo }}</th>
                @endforeach
                <th>VALOR</th>
                @if ($editable)
                    <th class="text-center" style="width: 70px;">ACCIÓN</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($filasMostradas as $i => $fila)
                <tr>
                    @foreach ($columnas as $campo => $titulo)
                        <td><input type="text" name="filas[{{ $i }}][{{ $campo }}]" class="form-control form-control-sm"
                                maxlength="{{ $campo === 'nombre' ? 120 : 200 }}" value="{{ $fila?->{$campo} }}" @disabled(! $editable)></td>
                    @endforeach
                    <td><input type="text" name="filas[{{ $i }}][valor]" class="form-control form-control-sm text-end"
                            inputmode="decimal" value="{{ $fila ? $pesosInput($fila->valor) : '' }}" @disabled(! $editable)></td>
                    @if ($editable)
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger quitar-fila" aria-label="Quitar fila">×</button></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="{{ count($columnas) }}" class="text-end">{{ $totalEtiqueta }}</td>
                <td class="text-end">{{ number_format((float) $total, 0, ',', '.') }}</td>
                @if ($editable)
                    <td></td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>

@if ($editable)
    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-primary agregar-fila-conteo">+ AGREGAR FILA</button>
    </div>

    <script>
        (function() {
            const tabla = document.querySelector('[data-filas-conteo]');
            if (!tabla) return;
            const cuerpo = tabla.querySelector('tbody');
            let siguiente = cuerpo.querySelectorAll('tr').length;

            document.querySelector('.agregar-fila-conteo').addEventListener('click', function() {
                const nueva = cuerpo.querySelector('tr').cloneNode(true);
                nueva.querySelectorAll('input').forEach(campo => {
                    campo.name = campo.name.replace(/filas\[\d+\]/, 'filas[' + siguiente + ']');
                    campo.value = '';
                    delete campo.dataset.decInit;
                });
                siguiente++;
                cuerpo.appendChild(nueva);
                window.initDecimalFormatters?.();
                nueva.querySelector('input')?.focus();
            });

            cuerpo.addEventListener('click', function(event) {
                const quitar = event.target.closest('.quitar-fila');
                if (!quitar) return;
                const fila = quitar.closest('tr');
                // Siempre queda una fila para digitar.
                if (cuerpo.querySelectorAll('tr').length > 1) {
                    fila.remove();
                } else {
                    fila.querySelectorAll('input').forEach(campo => campo.value = '');
                }
            });
        })();
    </script>
@endif
