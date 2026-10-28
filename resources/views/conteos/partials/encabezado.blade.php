{{-- Encabezado común de los submódulos del conteo: conteo, pestañas, informes y resumen. --}}
@php
    use App\Models\Conteo;

    $pesos = fn ($valor): string => number_format((float) $valor, 0, ',', '.');
    $claseValor = fn ($valor): string => round((float) $valor) < 0 ? 'text-danger fw-bold' : 'fw-bold';
@endphp

<style>
    .conteo-resumen .valor {
        text-align: right;
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

@unless ($editable)
    <div class="alert alert-warning">
        <i class="bi bi-lock-fill" aria-hidden="true"></i> Este conteo ya tiene conteos posteriores: solo se puede
        consultar. Los siguientes toman de él el último No. de consignación y el saldo de moneda.
    </div>
@endunless

<div class="card mb-3 conteo-resumen">
    <div class="card-body py-2">
        <div class="row g-2 small">
            <div class="col-6 col-md-3">TOTAL CIERRE<div class="valor {{ $claseValor($resumen['total_cierre']) }}">{{ $pesos($resumen['total_cierre']) }}</div></div>
            <div class="col-6 col-md-3">CONSIGNACIONES INTERNAS<div class="valor fw-bold">{{ $pesos($resumen['total_consignaciones']) }}</div></div>
            <div class="col-6 col-md-3">DIFERENCIA<div class="valor {{ $claseValor($resumen['diferencia']) }}">{{ $pesos($resumen['diferencia']) }}</div></div>
            <div class="col-6 col-md-3">A CONSIGNAR<div class="valor {{ $claseValor($resumen['a_consignar']) }}">{{ $pesos($resumen['a_consignar']) }}</div></div>
            <div class="col-6 col-md-3">CONSIGNACIONES Y OTROS<div class="valor fw-bold">{{ $pesos($resumen['aplicaciones']) }}</div></div>
            <div class="col-6 col-md-3">NO CONSIGNADO<div class="valor {{ $claseValor($resumen['no_consignado']) }}">{{ $pesos($resumen['no_consignado']) }}</div></div>
            <div class="col-6 col-md-3">SALDO DE MONEDA QUE PASA<div class="valor {{ $claseValor($resumen['saldo_moneda_pasa']) }}">{{ $pesos($resumen['saldo_moneda_pasa']) }}</div></div>
        </div>
    </div>
</div>
