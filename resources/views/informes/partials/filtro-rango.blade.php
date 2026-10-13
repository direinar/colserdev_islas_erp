{{--
    Filtro de periodo de los informes (dentro de <x-page-header>): desde/hasta, imprimir y Excel.
    Parámetros: $ruta (nombre de la ruta del informe), $rutaExportar, $desde, $hasta y, opcional,
    $filtrosExtra (otros filtros que se conservan al cambiar las fechas y al exportar).
--}}
@php($filtrosExtra = $filtrosExtra ?? [])
<form method="GET" action="{{ route($ruta) }}" class="d-flex flex-wrap gap-2 align-items-end">
    @foreach ($filtrosExtra as $nombre => $valor)
        <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
    @endforeach
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
<button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
    <i class="bi bi-printer" aria-hidden="true"></i> IMPRIMIR
</button>
<a href="{{ route($rutaExportar, $filtrosExtra + ['desde' => $desde, 'hasta' => $hasta]) }}" class="btn btn-outline-light btn-sm">
    <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> EXCEL
</a>
