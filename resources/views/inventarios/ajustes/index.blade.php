@extends('layouts.app')

@section('title', 'Ajustes de inventario')

@php
    use App\Http\Controllers\InventarioAjusteController;
    use App\Models\InventarioAjuste;
    use App\Models\InventarioSaldoInicial;
    use Illuminate\Support\Carbon;

    $cantidad = fn ($valor): string => rtrim(rtrim(number_format((float) $valor, 3, ',', '.'), '0'), ',');
    $rango = ['desde' => $desde, 'hasta' => $hasta];
@endphp

@section('content')
    <x-page-header title="Ajustes de inventario" icon="box-seam"
        subtitle="Salidas por avería, pérdida, vencimiento o consumo interno, y entradas por sobrante en un conteo físico. Cada ajuste aparece en su kardex al costo promedio; no es una venta.">
        <form method="GET" action="{{ route('inventarios-ajustes.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label" for="desde">DESDE</label>
                <input type="date" name="desde" id="desde" class="form-control form-control-sm" value="{{ $desde }}"
                    onchange="this.form.submit()">
            </div>
            <div>
                <label class="form-label" for="hasta">HASTA</label>
                <input type="date" name="hasta" id="hasta" class="form-control form-control-sm" value="{{ $hasta }}"
                    onchange="this.form.submit()">
            </div>
        </form>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <x-erp-card title="REGISTRAR AJUSTE">
        <form method="POST" action="{{ route('inventarios-ajustes.store', $rango) }}" class="p-3 row g-2 align-items-end"
            id="ajuste-form">
            @csrf
            <div class="col-6 col-md-2">
                <label class="form-label small mb-0" for="ajuste-fecha">FECHA</label>
                <input type="date" name="fecha" id="ajuste-fecha" class="form-control form-control-sm"
                    value="{{ old('fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-0" for="ajuste-inventario">INVENTARIO</label>
                <select name="inventario" id="ajuste-inventario" class="form-select form-select-sm" required>
                    @foreach (InventarioAjusteController::INVENTARIOS as $clave => $nombre)
                        <option value="{{ $clave }}" @selected(old('inventario', InventarioSaldoInicial::CANASTILLA) === $clave)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3" id="ajuste-referencia-campo">
                <label class="form-label small mb-0" for="ajuste-referencia">PRODUCTO (CANASTILLA)</label>
                <select name="referencia" id="ajuste-referencia" class="form-select form-select-sm">
                    <option value="">Seleccione producto</option>
                    @foreach ($referencias as $referencia)
                        <option value="{{ $referencia }}" @selected(old('referencia') === $referencia)>{{ $referencia }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-0" for="ajuste-motivo">TIPO DE AJUSTE</label>
                <select name="motivo" id="ajuste-motivo" class="form-select form-select-sm" required>
                    @foreach (InventarioAjuste::MOTIVOS as $clave => [$nombre, $entrada])
                        <option value="{{ $clave }}" @selected(old('motivo') === $clave)>{{ $nombre }} ({{ $entrada ? 'entrada' : 'salida' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small mb-0" for="ajuste-unidades">CANTIDAD</label>
                <input type="text" inputmode="decimal" name="unidades" id="ajuste-unidades"
                    class="form-control form-control-sm text-end" value="{{ old('unidades') }}" required>
            </div>
            <div class="col-12 col-md-10">
                <label class="form-label small mb-0" for="ajuste-observacion">MOTIVO / DETALLE</label>
                <input type="text" name="observacion" id="ajuste-observacion" class="form-control form-control-sm"
                    maxlength="500" value="{{ old('observacion') }}" required
                    placeholder="Ej.: envase roto en bodega; producto vencido; conteo físico del 15/11">
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">Registrar ajuste</button>
            </div>
        </form>
    </x-erp-card>

    <x-erp-card :title="'AJUSTES DEL '.Carbon::parse($desde)->format('d/m/Y').' AL '.Carbon::parse($hasta)->format('d/m/Y')" class="mt-3">
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>FECHA</th>
                        <th>INVENTARIO</th>
                        <th>PRODUCTO</th>
                        <th>TIPO</th>
                        <th class="text-end">ENTRADA</th>
                        <th class="text-end">SALIDA</th>
                        <th>MOTIVO / DETALLE</th>
                        <th>REGISTRÓ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ajustes as $ajuste)
                        <tr>
                            <td>{{ $ajuste->fecha->format('d/m/Y') }}</td>
                            <td>{{ InventarioAjusteController::INVENTARIOS[$ajuste->inventario] ?? $ajuste->inventario }}</td>
                            <td>{{ $ajuste->referencia }}</td>
                            <td>{{ $ajuste->nombreMotivo() }}</td>
                            <td class="text-end">{{ $ajuste->esEntrada() ? $cantidad($ajuste->unidades) : '' }}</td>
                            <td class="text-end">{{ $ajuste->esEntrada() ? '' : $cantidad($ajuste->unidades) }}</td>
                            <td>{{ $ajuste->observacion }}</td>
                            <td class="small">{{ $ajuste->user?->name }}<br>{{ $ajuste->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('inventarios-ajustes.destroy', ['ajuste' => $ajuste] + $rango) }}"
                                    class="m-0" onsubmit="return confirm('¿Eliminar este ajuste? El kardex se recalcula sin él.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar ajuste">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">No hay ajustes en este periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-erp-card>

    <script>
        // El producto solo aplica a canastilla.
        (function() {
            const inventario = document.getElementById('ajuste-inventario');
            const campo = document.getElementById('ajuste-referencia-campo');
            const referencia = document.getElementById('ajuste-referencia');
            const actualizar = () => {
                const esCanastilla = inventario.value === @json(InventarioSaldoInicial::CANASTILLA);
                campo.classList.toggle('d-none', !esCanastilla);
                referencia.required = esCanastilla;
                if (!esCanastilla) referencia.value = '';
            };
            inventario.addEventListener('change', actualizar);
            actualizar();
        })();
    </script>
@endsection
