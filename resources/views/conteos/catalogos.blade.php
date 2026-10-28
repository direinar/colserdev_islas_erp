@extends('layouts.app')

@section('title', 'Conteo - Destinos, bancos y terceros')

@section('content')
    <x-page-header title="Destinos, bancos y terceros" icon="bank"
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
    </div>
@endsection
