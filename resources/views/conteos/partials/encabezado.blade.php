{{-- Encabezado común de los submódulos del conteo: conteo, pestañas, informes y resumen. --}}
@php
    use App\Models\Conteo;

    $pesos = fn ($valor): string => number_format((float) $valor, 0, ',', '.');
    $claseValor = fn ($valor): string => round((float) $valor) < 0 ? 'text-danger fw-bold' : 'fw-bold';
@endphp

<style>
    .conteo-resumen .valor {
        text-align: right;
        white-space: nowrap;
    }

    .conteo-resumen-item {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: .5rem;
        padding-right: 1rem;
    }

    .conteo-tabla thead th {
        background: #eeece1;
        text-align: center;
    }

    .conteo-tabla .total td,
    .conteo-tabla .total th {
        font-weight: 700;
        background: #f2f2f2;
    }
</style>

<x-page-header :title="'Conteo No. '.$conteo->numero" icon="cash-stack"
    :subtitle="'Fecha '.$conteo->fecha->format('d/m/Y').' — '.Conteo::SECCIONES[$seccion]">
    <form method="GET" class="mb-0" onsubmit="return false;">
        <label class="form-label" for="conteo-selector">CONTEO</label>
        <select id="conteo-selector" class="form-select form-select-sm"
            onchange="window.location = this.value">
            @foreach ($listaConteos as $opcion)
                <option value="{{ route('conteos.seccion', [$opcion, $seccion]) }}" @selected($opcion->is($conteo))>
                    No. {{ $opcion->numero }} — {{ $opcion->fecha->format('d/m/Y') }}
                </option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('conteos.reporte', $conteo) }}" class="btn btn-outline-light btn-sm" target="_blank">
        <i class="bi bi-printer" aria-hidden="true"></i> IMPRIMIR
    </a>
    <a href="{{ route('conteos.exportar', $conteo) }}" class="btn btn-outline-light btn-sm">
        <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> EXCEL
    </a>
    <a href="{{ route('conteos.pdf', $conteo) }}" class="btn btn-outline-light btn-sm">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
    </a>
    <a href="{{ route('conteos.index') }}" class="btn btn-light btn-sm">
        <i class="bi bi-list" aria-hidden="true"></i> CONTEOS
    </a>
</x-page-header>

<ul class="nav nav-tabs mb-3">
    @foreach (Conteo::SECCIONES as $clave => $nombre)
        <li class="nav-item">
            <a class="nav-link {{ $clave === $seccion ? 'active fw-bold' : '' }}"
                href="{{ route('conteos.seccion', [$conteo, $clave]) }}">{{ $nombre }}</a>
        </li>
    @endforeach
</ul>

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

@unless ($editable || $seccion === 'comprobante')
    <div class="alert alert-warning">
        <i class="bi bi-lock-fill" aria-hidden="true"></i> Este conteo ya tiene conteos posteriores: solo se puede
        consultar. Los siguientes toman de él el último No. de consignación y el saldo de moneda.
    </div>
@endunless

<div class="card mb-3 conteo-resumen">
    <div class="card-body py-2">
        {{-- Cada valor en la misma línea de su texto, alineado a la derecha de su casilla. --}}
        <div class="row g-2 small">
            @foreach ([
                ['TOTAL CIERRE', 'total_cierre', true],
                ['CONSIGNACIONES INTERNAS', 'total_consignaciones', false],
                ['DIFERENCIA', 'diferencia', true],
                ['A CONSIGNAR', 'a_consignar', true],
                ['CONSIGNACIONES Y OTROS', 'aplicaciones', false],
                ['NO CONSIGNADO', 'no_consignado', true],
                ['SALDO DE MONEDA QUE PASA', 'saldo_moneda_pasa', true],
            ] as [$texto, $clave, $conSigno])
                <div class="col-6 col-md-3">
                    <div class="conteo-resumen-item">
                        <span>{{ $texto }}</span>
                        <span class="valor {{ $conSigno ? $claseValor($resumen[$clave]) : 'fw-bold' }}">{{ $pesos($resumen[$clave]) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
