@extends('layouts.app')

@section('title', 'Conteo - Retiros antes del cierre')

@section('content')
    @include('conteos.partials.encabezado')

    <form method="POST" action="{{ route('conteos.guardar', [$conteo, 'retiros']) }}">
        @csrf
        <div class="card">
            <div class="card-header fw-bold">RETIROS ANTES DE ESTE CIERRE</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Dinero que se retiró antes de este conteo. Su total se suma al TOTAL CIERRE
                    del cuadre de efectivo.</p>
                @include('conteos.partials.filas', [
                    'filas' => $conteo->movimientos->where('tipo', \App\Models\ConteoMovimiento::TIPO_RETIRO),
                    'columnas' => ['nombre' => 'NOMBRE', 'concepto' => 'CONCEPTO'],
                    'totalEtiqueta' => 'TOTAL RETIROS',
                    'total' => $resumen['retiros'],
                ])
            </div>
        </div>

        @if ($editable)
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> GUARDAR</button>
            </div>
        @endif
    </form>
@endsection
