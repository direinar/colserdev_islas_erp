@extends('layouts.app')

@section('title', 'Compras')

@php
    $pesos = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $galones = fn (float $valor): string => rtrim(rtrim(number_format($valor, 3, ',', '.'), '0'), ',');
    $columnasValor = ['base19_', 'base5_', 'iva_', 'costo_producto_', 'sobretasa_', 'soldicom_', 'distribucion_'];
@endphp

@section('content')
    <x-page-header title="Compras de combustible" icon="fuel-pump"
        subtitle="Registre cada factura con la liquidación de IVA y el costo de gasolina y ACPM.">
        <button type="submit" form="compras-form" class="btn btn-light">
            <i class="bi bi-save" aria-hidden="true"></i> Guardar factura
        </button>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-print-none">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <style>
        /* Compras con IVA: grupos de color por combustible, como en la plantilla. */
        .compras-tabla th,
        .compras-tabla td {
            white-space: nowrap;
            vertical-align: middle;
        }

        .compras-tabla thead th {
            font-size: .72rem;
            line-height: 1.15;
        }

        .compras-tabla .compra-grupo-gasolina,
        .compras-tabla .compra-sub-gasolina { background: #fbe5d6; }
        .compras-tabla .compra-grupo-acpm,
        .compras-tabla .compra-sub-acpm { background: #e2efda; }
        .compras-tabla .compra-grupo-total,
        .compras-tabla .compra-sub-total { background: #ddebf7; }
        .compras-tabla .compra-grupo-gasolina,
        .compras-tabla .compra-grupo-acpm,
        .compras-tabla .compra-grupo-total { font-size: .9rem; letter-spacing: .05rem; }

        .compras-tabla .compra-inicio-gasolina,
        .compras-tabla .compra-inicio-acpm,
        .compras-tabla .compra-inicio-total { border-left: 3px solid #595959; }

        .compras-tabla .compra-ref { color: #c00000; font-weight: 700; }

        /* Fórmulas: no se digitan. */
        .compras-tabla .compra-calc {
            background: #f2f2f2;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .compras-tabla .compra-costo { font-weight: 700; }

        .compras-tabla .compra-numero { min-width: 7.5rem; }
        .compras-tabla .compra-texto { min-width: 7rem; }
        .compras-tabla .compra-proveedor { min-width: 11rem; }
        .compras-tabla td.compra-calc { min-width: 7rem; }

        #compras-registradas { font-size: .78rem; }
        #compras-registradas td.num { text-align: right; font-variant-numeric: tabular-nums; }
        #compras-registradas tr:target td { background-color: #fff3cd; }
    </style>

    {{-- Al imprimir solo sale COMPRAS REGISTRADAS. --}}
    <form id="compras-form" method="POST" action="{{ route('compras.store') }}" class="d-print-none">
        @csrf

        <x-erp-card title="INFORMACION DE COMPRAS">
            <div class="d-flex justify-content-end p-3 pb-2">
                <button type="button" id="add-compra-row" class="btn btn-sm btn-outline-primary">+ Agregar fila</button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 compras-tabla" id="compras-table">
                    <thead>
                        @include('compras.partials.encabezado')
                    </thead>

                    <tbody id="compras-body">
                        @foreach (old('compras', [['fecha' => date('Y-m-d')]]) as $index => $row)
                            @include('compras.partials.fila', ['index' => $index, 'row' => $row])
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="3" class="text-end">TOTALES</td>
                            @foreach (['gasolina', 'acpm'] as $combustible)
                                <td class="text-end compra-inicio-{{ $combustible }}" data-total="{{ $combustible }}">0</td>
                                @foreach (['base19', 'base5', 'iva', 'costo_producto', 'sobretasa', 'soldicom', 'costo'] as $campo)
                                    <td class="text-end" data-total="{{ $campo }}_{{ $combustible }}">0</td>
                                @endforeach
                                <td></td>
                            @endforeach
                            <td class="text-end compra-inicio-total" data-total="vr_total_fra">0</td>
                            <td class="text-end" data-total="total">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="small text-muted p-3 pt-2">
                <span class="compra-ref text-danger fw-bold">(1)</span> Celdas blancas: las llena el administrador.
                <span class="text-danger fw-bold ms-2">(2)</span> Liquidación IVA = base 19% × 19% + base 5% × 5%.
                <span class="text-danger fw-bold ms-2">(3)</span> Costo = liquidación IVA + costo producto + sobretasa + Soldicom (alimenta los inventarios).
                <span class="text-danger fw-bold ms-2">(4)</span> Costo unitario = costo ÷ galones.
                Las celdas grises son fórmulas.
            </div>
        </x-erp-card>
    </form>

    {{-- Molde de las filas que se agregan con "+ Agregar fila". --}}
    <template id="compra-fila-template">
        @include('compras.partials.fila', ['index' => '__INDEX__', 'row' => ['fecha' => date('Y-m-d')]])
    </template>

    @include('informes.partials.impresion', ['horizontal' => true])

    <x-erp-card title="COMPRAS REGISTRADAS">
        {{-- Rango de fechas a la izquierda; imprimir y Excel a la derecha. --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 p-3 pb-2 d-print-none">
            <form method="GET" action="{{ route('compras.create') }}" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label for="compras-desde" class="form-label small mb-1">Desde</label>
                    <input type="date" id="compras-desde" name="desde" class="form-control form-control-sm"
                        value="{{ $desde }}">
                </div>
                <div>
                    <label for="compras-hasta" class="form-label small mb-1">Hasta</label>
                    <input type="date" id="compras-hasta" name="hasta" class="form-control form-control-sm"
                        value="{{ $hasta }}">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary">Consultar</button>
            </form>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer" aria-hidden="true"></i> IMPRIMIR
                </button>
                <a href="{{ route('compras.exportar', ['desde' => $desde, 'hasta' => $hasta]) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> EXCEL
                </a>
            </div>
        </div>
        <div class="d-none d-print-block px-3 small">
            Compras registradas del {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 compras-tabla" id="compras-registradas">
                <thead>
                    @include('compras.partials.encabezado', ['accionImprimible' => false])
                </thead>
                <tbody>
                    @forelse ($comprasRegistradas as $compra)
                        <tr id="compra-{{ $compra->id }}">
                            <td class="text-center">{{ $compra->fecha->format('d/m/Y') }}</td>
                            <td>{{ $compra->factura }}</td>
                            <td>{{ $compra->proveedor?->name }}</td>
                            @foreach (['gasolina', 'acpm'] as $combustible)
                                <td class="num compra-inicio-{{ $combustible }}">{{ $galones((float) $compra->{$combustible}) }}</td>
                                @foreach ($columnasValor as $prefijo)
                                    <td class="num {{ in_array($prefijo, ['iva_', 'distribucion_'], true) ? 'compra-calc' : '' }} {{ $prefijo === 'distribucion_' ? 'compra-costo' : '' }}">{{ $pesos((float) $compra->{$prefijo.$combustible}) }}</td>
                                @endforeach
                                <td class="num compra-calc">{{ number_format($compra->costoUnitario($combustible), 2, ',', '.') }}</td>
                            @endforeach
                            <td class="num compra-calc compra-costo compra-inicio-total">{{ $pesos((float) $compra->vr_total_fra) }}</td>
                            <td class="num compra-calc">{{ $galones((float) $compra->total) }}</td>
                            <td class="text-center text-nowrap d-print-none">
                                <a href="{{ route('compras.edit', $compra) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form method="POST" action="{{ route('compras.destroy', $compra) }}" class="d-inline"
                                    onsubmit="return confirm('¿Eliminar la compra {{ $compra->factura }}? El inventario se recalcula sin ella.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="24" class="text-center text-muted py-3">No hay compras en el rango seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td colspan="3" class="text-end">TOTALES</td>
                        @foreach (['gasolina', 'acpm'] as $combustible)
                            <td class="num compra-inicio-{{ $combustible }}">{{ $galones((float) $comprasRegistradas->sum($combustible)) }}</td>
                            @foreach ($columnasValor as $prefijo)
                                <td class="num">{{ $pesos((float) $comprasRegistradas->sum($prefijo.$combustible)) }}</td>
                            @endforeach
                            <td></td>
                        @endforeach
                        <td class="num compra-inicio-total">{{ $pesos((float) $comprasRegistradas->sum('vr_total_fra')) }}</td>
                        <td class="num">{{ $galones((float) $comprasRegistradas->sum('total')) }}</td>
                        <td class="d-print-none"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-erp-card>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('compras-body');
            const plantilla = document.getElementById('compra-fila-template');
            const IVA_GENERAL = {{ \App\Models\Compra::TARIFA_IVA_GENERAL }};
            const IVA_REDUCIDO = {{ \App\Models\Compra::TARIFA_IVA_REDUCIDA }};
            const COMBUSTIBLES = ['gasolina', 'acpm'];
            // Índice libre para las filas nuevas (tras un error se recargan las filas con sus índices).
            let siguiente = Math.max(-1, ...[...tbody.querySelectorAll('tr')].map(fila => Number(fila.dataset.index) || 0)) + 1;

            // Formato es-CO: el punto separa miles y la coma los decimales.
            const leer = (valor) => Number(String(valor ?? '').trim().replace(/\s+/g, '').replace(/\./g, '').replace(/,/g, '.')) || 0;
            const pesos = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
            const unitario = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const galones = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 3 });

            function valor(fila, campo) {
                return leer(fila.querySelector(`[data-campo="${campo}"]`)?.value);
            }

            function mostrar(fila, campo, texto) {
                const salida = fila.querySelector(`[data-calc="${campo}"]`);
                if (salida) salida.textContent = texto;
            }

            // Fórmulas de la plantilla: (2) IVA, (3) costo y (4) costo unitario.
            function liquidarFila(fila) {
                const resultado = { vr_total_fra: 0, total: 0 };

                for (const c of COMBUSTIBLES) {
                    const iva = valor(fila, `base19_${c}`) * IVA_GENERAL + valor(fila, `base5_${c}`) * IVA_REDUCIDO;
                    const costo = iva + valor(fila, `costo_producto_${c}`) + valor(fila, `sobretasa_${c}`) + valor(fila, `soldicom_${c}`);
                    const gal = valor(fila, c);

                    resultado[`iva_${c}`] = iva;
                    resultado[`costo_${c}`] = costo;
                    resultado.vr_total_fra += costo;
                    resultado.total += gal;

                    mostrar(fila, `iva_${c}`, pesos.format(iva));
                    mostrar(fila, `costo_${c}`, pesos.format(costo));
                    mostrar(fila, `unitario_${c}`, unitario.format(gal > 0 ? costo / gal : 0));
                }

                mostrar(fila, 'vr_total_fra', pesos.format(resultado.vr_total_fra));
                mostrar(fila, 'total', galones.format(resultado.total));

                return resultado;
            }

            function actualizarTotales() {
                const totales = {};
                const sumar = (campo, cantidad) => totales[campo] = (totales[campo] ?? 0) + cantidad;

                tbody.querySelectorAll('tr').forEach(fila => {
                    const resultado = liquidarFila(fila);

                    for (const c of COMBUSTIBLES) {
                        sumar(c, valor(fila, c));
                        for (const campo of ['base19', 'base5', 'costo_producto', 'sobretasa', 'soldicom']) {
                            sumar(`${campo}_${c}`, valor(fila, `${campo}_${c}`));
                        }
                        sumar(`iva_${c}`, resultado[`iva_${c}`]);
                        sumar(`costo_${c}`, resultado[`costo_${c}`]);
                    }
                    sumar('vr_total_fra', resultado.vr_total_fra);
                    sumar('total', resultado.total);
                });

                document.querySelectorAll('#compras-table [data-total]').forEach(celda => {
                    const campo = celda.dataset.total;
                    const esGalones = COMBUSTIBLES.includes(campo) || campo === 'total';
                    celda.textContent = (esGalones ? galones : pesos).format(totales[campo] ?? 0);
                });
            }

            function agregarFila() {
                const html = plantilla.innerHTML.replaceAll('__INDEX__', siguiente++);
                tbody.insertAdjacentHTML('beforeend', html);
                actualizarTotales();
            }

            tbody.addEventListener('input', actualizarTotales);
            tbody.addEventListener('change', actualizarTotales);

            document.getElementById('add-compra-row').addEventListener('click', agregarFila);

            tbody.addEventListener('click', function(evento) {
                if (!evento.target.classList.contains('remove-row')) return;

                const filas = tbody.querySelectorAll('tr');
                if (filas.length === 1) {
                    filas[0].querySelectorAll('input:not([type="date"]), select').forEach(campo => campo.value = '');
                } else {
                    evento.target.closest('tr').remove();
                }

                actualizarTotales();
            });

            actualizarTotales();
        });
    </script>
@endsection
