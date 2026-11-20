@extends('layouts.app')

@section('title', 'Conteo - Destinos, terceros y cuentas contables')

@section('content')
    <x-page-header title="Destinos, terceros y cuentas contables" icon="bank"
        subtitle="Tablas de la consignación en bancos y otras aplicaciones del conteo.">
        <a href="{{ route('conteos.index') }}" class="btn btn-light btn-sm">
            <i class="bi bi-list" aria-hidden="true"></i> CONTEOS
        </a>
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

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header fw-bold">DESTINOS (TABLA DE CONCEPTOS)</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('conteos.destinos.store') }}" class="d-flex gap-2 mb-3">
                        @csrf
                        <input type="text" name="nombre" class="form-control form-control-sm" maxlength="120" required
                            placeholder="Nuevo destino" aria-label="Nuevo destino">
                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">+ AGREGAR</button>
                    </form>
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            @foreach ($destinos as $destino)
                                <tr>
                                    <td>
                                        <form method="POST" action="{{ route('conteos.destinos.update', $destino) }}" class="d-flex gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="nombre" class="form-control form-control-sm" maxlength="120" required
                                                value="{{ $destino->nombre }}" aria-label="Nombre del destino">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
                                        </form>
                                    </td>
                                    <td class="text-center" style="width: 60px;">
                                        @if ($destino->aplicaciones_count === 0)
                                            <form method="POST" action="{{ route('conteos.destinos.destroy', $destino) }}"
                                                onsubmit="return confirm('¿Eliminar este destino?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" aria-label="Eliminar">×</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header fw-bold">BANCOS Y TERCEROS</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('conteos.terceros.store') }}" class="d-flex gap-2 mb-3">
                        @csrf
                        <input type="text" name="nombre" class="form-control form-control-sm" maxlength="120" required
                            placeholder="Nuevo banco o tercero" aria-label="Nuevo banco o tercero">
                        <select name="tipo" class="form-select form-select-sm" style="max-width: 130px;" aria-label="Tipo">
                            @foreach (\App\Models\ConteoTercero::TIPOS as $clave => $nombre)
                                <option value="{{ $clave }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">+ AGREGAR</button>
                    </form>
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            @foreach ($terceros as $tercero)
                                <tr>
                                    <td>
                                        <form method="POST" action="{{ route('conteos.terceros.update', $tercero) }}" class="d-flex gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="nombre" class="form-control form-control-sm" maxlength="120" required
                                                value="{{ $tercero->nombre }}" aria-label="Nombre del banco o tercero">
                                            <select name="tipo" class="form-select form-select-sm" style="max-width: 130px;" aria-label="Tipo">
                                                @foreach (\App\Models\ConteoTercero::TIPOS as $clave => $nombre)
                                                    <option value="{{ $clave }}" @selected($tercero->tipo === $clave)>{{ $nombre }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
                                        </form>
                                    </td>
                                    <td class="text-center" style="width: 60px;">
                                        @if ($tercero->aplicaciones_count === 0)
                                            <form method="POST" action="{{ route('conteos.terceros.destroy', $tercero) }}"
                                                onsubmit="return confirm('¿Eliminar este banco o tercero?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" aria-label="Eliminar">×</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12" id="cuentas">
            <div class="card">
                <div class="card-header fw-bold d-flex flex-wrap justify-content-between gap-2">
                    <span>TABLA DE CUENTAS, CONCEPTOS, TERCEROS Y NIT (COMPROBANTE CONTABLE DE CONTEO)</span>
                    <a href="{{ route('conteos.comprobante.index') }}" class="btn btn-sm btn-outline-secondary fw-normal">Comprobante contable de conteo</a>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Cada línea de la consignación en bancos se contabiliza con una de estas cuentas. La caja
                        (11050501) se acredita sola por el total consignado.</p>
                    <form method="POST" action="{{ route('conteos.cuentas.store') }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-6 col-md-2"><input type="text" name="cuenta" class="form-control form-control-sm" inputmode="numeric" maxlength="20" required placeholder="No. de cuenta" aria-label="No. de cuenta"></div>
                        <div class="col-6 col-md-4"><input type="text" name="concepto" class="form-control form-control-sm" maxlength="150" required placeholder="Concepto" aria-label="Concepto"></div>
                        <div class="col-6 col-md-3"><input type="text" name="tercero" class="form-control form-control-sm" maxlength="120" placeholder="Tercero" aria-label="Tercero"></div>
                        <div class="col-4 col-md-2"><input type="text" name="nit" class="form-control form-control-sm" maxlength="30" placeholder="NIT" aria-label="NIT"></div>
                        <div class="col-2 col-md-1"><button type="submit" class="btn btn-sm btn-primary w-100 text-nowrap">+ AGREGAR</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr><th style="width:14%;">No. DE CUENTA</th><th>CONCEPTO</th><th>TERCERO</th><th style="width:14%;">NIT</th><th style="width:150px;"></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($cuentas as $cuenta)
                                    <tr>
                                        <td colspan="4" class="p-1">
                                            <form method="POST" action="{{ route('conteos.cuentas.update', $cuenta) }}" class="row g-1" id="cuenta-{{ $cuenta->id }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-md-2"><input type="text" name="cuenta" class="form-control form-control-sm" value="{{ $cuenta->cuenta }}" maxlength="20" required aria-label="No. de cuenta"></div>
                                                <div class="col-md-5"><input type="text" name="concepto" class="form-control form-control-sm" value="{{ $cuenta->concepto }}" maxlength="150" required aria-label="Concepto"></div>
                                                <div class="col-md-3"><input type="text" name="tercero" class="form-control form-control-sm" value="{{ $cuenta->tercero }}" maxlength="120" aria-label="Tercero"></div>
                                                <div class="col-md-2"><input type="text" name="nit" class="form-control form-control-sm" value="{{ $cuenta->nit }}" maxlength="30" aria-label="NIT"></div>
                                            </form>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <button type="submit" form="cuenta-{{ $cuenta->id }}" class="btn btn-sm btn-outline-primary">Guardar</button>
                                            @if ($cuenta->aplicaciones_count === 0)
                                                <form method="POST" action="{{ route('conteos.cuentas.destroy', $cuenta) }}" class="d-inline"
                                                    onsubmit="return confirm('¿Eliminar esta cuenta de la tabla?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" aria-label="Eliminar">×</button>
                                                </form>
                                            @else
                                                <span class="badge text-bg-light border" title="Usada en comprobantes">{{ $cuenta->aplicaciones_count }} uso(s)</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
