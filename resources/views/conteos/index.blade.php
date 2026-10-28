@extends('layouts.app')

@section('title', 'Conteo')

@section('content')
    <x-page-header title="Conteo" icon="cash-stack" subtitle="Cuadre del efectivo de la EDS: billetes, moneda, consignaciones, gastos y saldo de moneda.">
        <a href="{{ route('conteos.catalogos') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-bank" aria-hidden="true"></i> Destinos, bancos y terceros
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

    <form method="POST" action="{{ route('conteos.store') }}" class="card mb-3">
        @csrf
        <div class="card-header fw-bold">NUEVO CONTEO</div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-sm-3">
                    <label class="form-label fw-semibold" for="fecha">FECHA</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" required
                        value="{{ old('fecha', now()->toDateString()) }}">
                </div>
                @if ($esPrimero)
                    {{-- Primer conteo: punto de partida, como el saldo inicial de cartera. --}}
                    <div class="col-sm-3">
                        <label class="form-label fw-semibold" for="consignacion-anterior">ÚLTIMO No. DE CONSIGNACIÓN ANTERIOR</label>
                        <input type="number" name="consignacion_anterior" id="consignacion-anterior" min="0" class="form-control"
                            value="{{ old('consignacion_anterior') }}" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label fw-semibold" for="saldo-moneda-inicial">SALDO INICIAL DE MONEDA</label>
                        <input type="text" name="saldo_moneda_inicial" id="saldo-moneda-inicial" inputmode="decimal"
                            class="form-control text-end" value="{{ old('saldo_moneda_inicial') }}" required>
                    </div>
                @endif
                <div class="col-sm-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> CREAR CONTEO
                    </button>
                </div>
            </div>
            @if ($esPrimero)
                <p class="small text-muted mt-2 mb-0">Es el primer conteo: digite el último No. de consignación interna ya
                    contado y el saldo de moneda con el que arranca. Los siguientes conteos los toman del conteo anterior.</p>
            @endif
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-primary">
                <tr>
                    <th>No.</th>
                    <th>FECHA</th>
                    <th>REGISTRÓ</th>
                    <th class="text-center">ABRIR</th>
                    <th class="text-center">INFORME</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($conteos as $conteo)
                    <tr>
                        <td>{{ $conteo->numero }}</td>
                        <td>{{ $conteo->fecha->format('d/m/Y') }}</td>
                        <td>{{ $conteo->user?->name }}</td>
                        <td class="text-center">
                            <a href="{{ route('conteos.seccion', [$conteo, 'efectivo']) }}" class="btn btn-sm btn-outline-primary">Abrir</a>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('conteos.reporte', $conteo) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Imprimir</a>
                            <a href="{{ route('conteos.exportar', $conteo) }}" class="btn btn-sm btn-outline-success">Excel</a>
                            <a href="{{ route('conteos.pdf', $conteo) }}" class="btn btn-sm btn-outline-danger">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No hay conteos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $conteos->links() }}
@endsection
