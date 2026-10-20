@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $pesos = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.');
    $galones = fn (float $valor): string => number_format($valor, 3, '.', ',');
    $porDia = $agrupacion === 'dia';
    $usuario = auth()->user();
@endphp

@section('content')
    <x-page-header title="Dashboard" icon="speedometer2"
        subtitle="Ventas y planillas de turnos del {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}{{ $usuario->isIslero() ? ' (sus planillas)' : '' }}.">
        <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap gap-2 align-items-end">
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
        @if ($usuario->isAdministrador())
            <a href="{{ route('users.index') }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-people" aria-hidden="true"></i> Gestionar usuarios
            </a>
        @endif
    </x-page-header>

    {{-- Indicadores del periodo --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="dashboard-kpi">
                <span class="dashboard-kpi-titulo">Ventas del periodo</span>
                <span class="dashboard-kpi-valor">{{ $pesos($kpis['ventas']) }}</span>
                <span class="dashboard-kpi-detalle">Combustible {{ $pesos($kpis['combustible']) }} · Canastilla {{ $pesos($kpis['canastilla']) }}</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dashboard-kpi">
                <span class="dashboard-kpi-titulo">Galones vendidos</span>
                <span class="dashboard-kpi-valor">{{ $galones($kpis['galones_corriente'] + $kpis['galones_acpm']) }}</span>
                <span class="dashboard-kpi-detalle">Corriente {{ $galones($kpis['galones_corriente']) }} · ACPM {{ $galones($kpis['galones_acpm']) }}</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dashboard-kpi">
                <span class="dashboard-kpi-titulo">Planillas de turno</span>
                <span class="dashboard-kpi-valor">{{ $kpis['turnos'] }}</span>
                <span class="dashboard-kpi-detalle">
                    @if ($kpis['pendientes_revision'] > 0)
                        <i class="bi bi-hourglass-split estado-advertencia" aria-hidden="true"></i>
                        {{ $kpis['pendientes_revision'] }} pendiente(s) de revisión
                    @else
                        <i class="bi bi-check-circle-fill estado-bien" aria-hidden="true"></i> Todas revisadas
                    @endif
                </span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="dashboard-kpi">
                <span class="dashboard-kpi-titulo">Sobrantes o faltantes sin trasladar</span>
                <span class="dashboard-kpi-valor">{{ $kpis['descuadrados'] }}</span>
                <span class="dashboard-kpi-detalle">
                    @if ($kpis['descuadrados'] > 0)
                        <i class="bi bi-exclamation-triangle-fill estado-critico" aria-hidden="true"></i> Planillas sin cuadrar
                    @else
                        <i class="bi bi-check-circle-fill estado-bien" aria-hidden="true"></i> Todas cuadradas
                    @endif
                </span>
            </div>
        </div>
    </div>

    @if ($kpis['turnos'] === 0)
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bar-chart-line fs-1 text-muted" aria-hidden="true"></i>
                <h2 class="h5 mt-3">No hay planillas de turno en este periodo</h2>
                <p class="text-muted mb-3">Las gráficas se alimentan de la planilla de turnos. Cambie el rango de fechas o registre una planilla.</p>
                <a href="{{ route('turnos.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-journal-plus" aria-hidden="true"></i> Ir a la planilla de turnos
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            <div class="col-lg-8">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Ventas por {{ $porDia ? 'día' : 'mes' }} <span class="text-muted fw-normal">(cierres IAPROPIADA + canastilla con IVA)</span></h2>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart">
                            <canvas id="ventasChart" role="img" aria-label="Ventas de gasolina, ACPM y canastilla por {{ $porDia ? 'día' : 'mes' }}"></canvas>
                        </div>
                        <details class="dashboard-tabla mt-2">
                            <summary>Ver datos en tabla</summary>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ $porDia ? 'Día' : 'Mes' }}</th>
                                            <th scope="col" class="text-end">Gasolina</th>
                                            <th scope="col" class="text-end">ACPM</th>
                                            <th scope="col" class="text-end">Canastilla</th>
                                            <th scope="col" class="text-end">Galones corriente</th>
                                            <th scope="col" class="text-end">Galones ACPM</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($periodos as $periodo)
                                            <tr>
                                                <th scope="row">{{ $periodo['etiqueta'] }}</th>
                                                <td class="text-end">{{ $pesos($periodo['gasolina']) }}</td>
                                                <td class="text-end">{{ $pesos($periodo['acpm']) }}</td>
                                                <td class="text-end">{{ $pesos($periodo['canastilla']) }}</td>
                                                <td class="text-end">{{ $galones($periodo['galones_corriente']) }}</td>
                                                <td class="text-end">{{ $galones($periodo['galones_acpm']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </div>
                </section>
            </div>

            <div class="col-lg-4">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Formas de pago recibidas</h2>
                    </div>
                    <div class="card-body">
                        @if ($formas_pago === [])
                            <p class="text-muted mb-0">Sin pagos registrados en el periodo.</p>
                        @else
                            <div class="dashboard-chart">
                                <canvas id="formasPagoChart" role="img" aria-label="Valor recibido por forma de pago"></canvas>
                            </div>
                            <details class="dashboard-tabla mt-2">
                                <summary>Ver datos en tabla</summary>
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        @foreach ($formas_pago as $forma)
                                            <tr>
                                                <th scope="row">{{ $forma['etiqueta'] }}</th>
                                                <td class="text-end">{{ $pesos($forma['valor']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </details>
                        @endif
                    </div>
                </section>
            </div>

            <div class="col-lg-8">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Galones vendidos por {{ $porDia ? 'día' : 'mes' }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-chart">
                            <canvas id="galonesChart" role="img" aria-label="Galones de corriente, ACPM y total por {{ $porDia ? 'día' : 'mes' }}; los datos están en la tabla de ventas"></canvas>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-lg-4">
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Canastilla: lo más vendido</h2>
                    </div>
                    <div class="card-body">
                        @if ($productos === [])
                            <p class="text-muted mb-0">Sin ventas de canastilla en el periodo.</p>
                        @else
                            <div class="dashboard-chart">
                                <canvas id="productosChart" role="img" aria-label="Referencias de canastilla con mayor venta"></canvas>
                            </div>
                            <details class="dashboard-tabla mt-2">
                                <summary>Ver datos en tabla</summary>
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Referencia</th>
                                            <th scope="col" class="text-end">Unid.</th>
                                            <th scope="col" class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($productos as $producto)
                                            <tr>
                                                <th scope="row">{{ $producto['producto'] }}</th>
                                                <td class="text-end">{{ number_format($producto['cantidad'], fmod($producto['cantidad'], 1.0) == 0.0 ? 0 : 2, ',', '.') }}</td>
                                                <td class="text-end">{{ $pesos($producto['total']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </details>
                        @endif
                    </div>
                </section>
            </div>

            <div class="col-12">
                <section class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Últimas planillas del periodo</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Turno</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Vendedor</th>
                                    <th scope="col" class="text-end">Ventas</th>
                                    <th scope="col">Cuadre</th>
                                    <th scope="col">Revisión</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ultimos as $turno)
                                    @php($pendiente = $turno->sobrante_pendiente === null ? null : (float) $turno->sobrante_pendiente)
                                    <tr>
                                        <td>
                                            <a href="{{ route('turnos.create', ['fecha' => $turno->fecha->format('Y-m-d'), 'numero_turno' => $turno->numero_turno]) }}">#{{ $turno->numero_turno }}</a>
                                        </td>
                                        <td>{{ $turno->fecha->format('d/m/Y') }}</td>
                                        <td>{{ $turno->nombre_vendedor }}</td>
                                        <td class="text-end">{{ $pesos((float) $turno->tirillas_valor_corriente + (float) $turno->tirillas_valor_acpm + (float) $turno->canastilla_total) }}</td>
                                        <td>
                                            @if ($pendiente === null)
                                                <span class="estado"><i class="bi bi-dash-circle estado-neutro" aria-hidden="true"></i> Sin calcular</span>
                                            @elseif (abs($pendiente) < 1)
                                                <span class="estado"><i class="bi bi-check-circle-fill estado-bien" aria-hidden="true"></i> Cuadrada</span>
                                            @else
                                                <span class="estado"><i class="bi bi-exclamation-triangle-fill estado-critico" aria-hidden="true"></i> {{ $pendiente < 0 ? 'Faltante' : 'Sobrante' }} {{ $pesos(abs($pendiente)) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($turno->revisado)
                                                <span class="estado"><i class="bi bi-patch-check-fill estado-bien" aria-hidden="true"></i> Revisada</span>
                                            @else
                                                <span class="estado"><i class="bi bi-hourglass-split estado-advertencia" aria-hidden="true"></i> Pendiente</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Chart === 'undefined') {
                    return;
                }

                // Paleta categórica validada (orden fijo): gasolina, ACPM, canastilla.
                const color = {
                    gasolina: '#2a78d6',
                    acpm: '#eb6834',
                    canastilla: '#1baf7a',
                    // Total de galones (corriente + ACPM).
                    totalGalones: '#15803d',
                    tinta: '#3b4046',
                    tenue: '#6b7280',
                    rejilla: '#eceef1',
                    superficie: '#ffffff',
                };
                const periodos = @json($periodos);
                const formasPago = @json($formas_pago);
                const productos = @json($productos);
                const pesos = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
                const galones = new Intl.NumberFormat('en-US', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
                const pesosCortos = new Intl.NumberFormat('es-CO', { notation: 'compact', maximumFractionDigits: 1 });

                Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
                Chart.defaults.color = color.tenue;

                const tooltip = (formato) => ({
                    backgroundColor: color.superficie,
                    titleColor: color.tinta,
                    bodyColor: color.tinta,
                    borderColor: '#d9dce1',
                    borderWidth: 1,
                    padding: 10,
                    usePointStyle: true,
                    callbacks: {
                        label: (contexto) => ' ' + (contexto.dataset.label ? contexto.dataset.label + ': ' : '') + formato(contexto.parsed[contexto.chart.options.indexAxis === 'y' ? 'x' : 'y']),
                    },
                });
                const leyenda = {
                    position: 'top',
                    align: 'start',
                    labels: { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, boxHeight: 10, color: color.tinta },
                };
                const ejeCategorias = { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, autoSkipPadding: 12 } };
                const ejeValores = (formato) => ({
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: color.rejilla },
                    ticks: { callback: formato, maxTicksLimit: 6 },
                });
                const barra = (etiqueta, datos, tono) => ({
                    label: etiqueta,
                    data: datos,
                    backgroundColor: tono,
                    borderColor: color.superficie,
                    borderWidth: { top: 2 },
                    borderRadius: 4,
                    borderSkipped: 'start',
                    maxBarThickness: 28,
                });

                new Chart(document.getElementById('ventasChart'), {
                    type: 'bar',
                    data: {
                        labels: periodos.map((p) => p.etiqueta),
                        datasets: [
                            barra('Gasolina', periodos.map((p) => p.gasolina), color.gasolina),
                            barra('ACPM', periodos.map((p) => p.acpm), color.acpm),
                            barra('Canastilla', periodos.map((p) => p.canastilla), color.canastilla),
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: leyenda,
                            tooltip: {
                                ...tooltip((valor) => pesos.format(valor)),
                                callbacks: {
                                    label: (c) => ' ' + c.dataset.label + ': ' + pesos.format(c.parsed.y),
                                    footer: (items) => 'Total: ' + pesos.format(items.reduce((suma, item) => suma + item.parsed.y, 0)),
                                },
                                footerColor: color.tinta,
                            },
                        },
                        scales: {
                            x: { ...ejeCategorias, stacked: true },
                            y: { ...ejeValores((valor) => '$ ' + pesosCortos.format(valor)), stacked: true },
                        },
                    },
                });

                const linea = (etiqueta, datos, tono) => ({
                    label: etiqueta,
                    data: datos,
                    borderColor: tono,
                    backgroundColor: tono,
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: periodos.length > 20 ? 0 : 3,
                    pointHoverRadius: 5,
                    pointHoverBorderColor: color.superficie,
                    pointHoverBorderWidth: 2,
                });

                new Chart(document.getElementById('galonesChart'), {
                    type: 'line',
                    data: {
                        labels: periodos.map((p) => p.etiqueta),
                        datasets: [
                            linea('Corriente', periodos.map((p) => p.galones_corriente), color.gasolina),
                            linea('ACPM', periodos.map((p) => p.galones_acpm), color.acpm),
                            linea('Total', periodos.map((p) => Math.round((p.galones_corriente + p.galones_acpm) * 1000) / 1000), color.totalGalones),
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: leyenda,
                            tooltip: tooltip((valor) => galones.format(valor) + ' gal'),
                        },
                        scales: {
                            x: ejeCategorias,
                            y: ejeValores((valor) => galones.format(valor).replace(/\.000$/, '')),
                        },
                    },
                });

                const barrasHorizontales = (lienzo, etiquetas, datos, nombre) => {
                    const elemento = document.getElementById(lienzo);

                    if (!elemento) {
                        return;
                    }

                    new Chart(elemento, {
                        type: 'bar',
                        data: {
                            labels: etiquetas,
                            datasets: [{
                                label: nombre,
                                data: datos,
                                backgroundColor: color.gasolina,
                                borderRadius: 4,
                                borderSkipped: 'start',
                                maxBarThickness: 22,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: tooltip((valor) => pesos.format(valor)),
                            },
                            scales: {
                                x: ejeValores((valor) => '$ ' + pesosCortos.format(valor)),
                                y: { grid: { display: false }, border: { display: false }, ticks: { color: color.tinta } },
                            },
                        },
                    });
                };

                barrasHorizontales('formasPagoChart', formasPago.map((f) => f.etiqueta), formasPago.map((f) => f.valor), 'Valor');
                barrasHorizontales('productosChart', productos.map((p) => p.producto), productos.map((p) => p.total), 'Venta con IVA');
            });
        </script>
    @endif
@endsection
