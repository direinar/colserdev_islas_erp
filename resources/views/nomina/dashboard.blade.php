@extends('layouts.app')

@section('title', 'Nómina')

@section('content')
    <x-page-header title="Nómina" icon="person-vcard"
        subtitle="Bienvenido(a), {{ $usuario->name }}. Empleados, planilla de turnos y liquidación de EDS El Portal de Tunja.">
        <a href="{{ route('nomina.empleados.index') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-people" aria-hidden="true"></i> Empleados
        </a>
        <a href="{{ route('nomina.planillas-turnos.index') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-calendar3" aria-hidden="true"></i> Planilla de turnos
        </a>
        <a href="{{ route('nomina.nominas.index') }}" class="btn btn-light btn-sm">
            <i class="bi bi-cash-coin" aria-hidden="true"></i> Nómina del mes
        </a>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <a href="{{ route('nomina.empleados.index') }}" class="text-decoration-none">
                <div class="dashboard-kpi">
                    <span class="dashboard-kpi-titulo">Empleados activos</span>
                    <span class="dashboard-kpi-valor">{{ $empleadosActivos }}</span>
                    <span class="dashboard-kpi-detalle">
                        @if ($contratosPorVencer > 0)
                            <i class="bi bi-exclamation-triangle-fill estado-advertencia" aria-hidden="true"></i>
                            {{ $contratosPorVencer }} contrato(s) por vencer en 30 días
                        @else
                            Sin contratos por vencer
                        @endif
                    </span>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('nomina.planillas-turnos.index') }}" class="text-decoration-none">
                <div class="dashboard-kpi">
                    <span class="dashboard-kpi-titulo">Planillas de turnos abiertas</span>
                    <span class="dashboard-kpi-valor">{{ $planillasAbiertas }}</span>
                    <span class="dashboard-kpi-detalle">En borrador, sin cerrar</span>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="{{ $ultimaNomina ? route('nomina.nominas.show', $ultimaNomina) : route('nomina.nominas.index') }}" class="text-decoration-none">
                <div class="dashboard-kpi">
                    <span class="dashboard-kpi-titulo">Última nómina</span>
                    @if ($ultimaNomina)
                        <span class="dashboard-kpi-valor">${{ number_format((float) $ultimaNomina->neto_total, 0, ',', '.') }}</span>
                        <span class="dashboard-kpi-detalle">
                            {{ $ultimaNomina->nombreDelMes() }} ·
                            @if ($ultimaNomina->estaAprobada())
                                <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> Aprobada
                            @else
                                <i class="bi bi-pencil-fill estado-advertencia" aria-hidden="true"></i> En borrador
                            @endif
                        </span>
                    @else
                        <span class="dashboard-kpi-valor">Sin liquidar</span>
                        <span class="dashboard-kpi-detalle">Cree la nómina del mes.</span>
                    @endif
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="{{ route('nomina.parametros-liquidacion.index') }}" class="text-decoration-none">
                <div class="dashboard-kpi">
                    <span class="dashboard-kpi-titulo">Parámetros de liquidación vigentes</span>
                    @if ($parametrosVigentes)
                        <span class="dashboard-kpi-valor">SMLMV ${{ number_format((float) $parametrosVigentes->salario_minimo, 0, ',', '.') }}</span>
                        <span class="dashboard-kpi-detalle">Desde {{ $parametrosVigentes->vigente_desde->format('d/m/Y') }} ·
                            Auxilio de transporte ${{ number_format((float) $parametrosVigentes->auxilio_transporte, 0, ',', '.') }}</span>
                    @else
                        <span class="dashboard-kpi-valor">Sin registrar</span>
                        <span class="dashboard-kpi-detalle">Registre los parámetros de la vigencia para liquidar.</span>
                    @endif
                </div>
            </a>
        </div>
    </div>
@endsection
