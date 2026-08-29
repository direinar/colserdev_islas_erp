@extends('layouts.app')

@section('content')
    <div class="pastel-section mb-3 d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Planillas pendientes de revisión</h4>
        <a href="{{ route('turnos.create') }}" class="btn btn-sm btn-outline-secondary">Volver a turnos</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="pastel-section">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Turno</th>
                        <th>Vendedor</th>
                        <th>Estado</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($turnos as $turno)
                        <tr>
                            <td>{{ $turno->fecha->format('d/m/Y') }}</td>
                            <td>{{ str_pad($turno->numero_turno, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $turno->nombre_vendedor ?? '—' }}</td>
                            <td><span class="badge bg-danger">PENDIENTE</span></td>
                            <td class="text-center d-flex gap-2 justify-content-center">
                                <a href="{{ route('turnos.create', ['fecha' => $turno->fecha->format('Y-m-d'), 'numero_turno' => $turno->numero_turno]) }}"
                                    class="btn btn-sm btn-outline-primary">Ver</a>
                                <form method="POST" action="{{ route('turnos.revisar', $turno) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Marcar revisado</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">No hay planillas pendientes de revisión.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $turnos->links() }}
    </div>
@endsection
