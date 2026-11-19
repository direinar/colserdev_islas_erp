@extends('layouts.app')

@section('title', 'Certificación laboral')

@section('content')
    <div class="container" style="max-width: 900px;">
        <x-page-header :title="'Certificación laboral · '.$empleado->nombre_completo" icon="award"
            subtitle="Los datos salen de la ficha del empleado. Ajuste a quién va dirigida y si incluye el salario.">
            <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
            <a href="{{ route('nomina.empleados.certificacion', [$empleado, 'dirigido_a' => $dirigidoA, 'incluir_salario' => $incluirSalario ? 1 : 0, 'pdf' => 1]) }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
            </a>
            <a href="{{ route('nomina.empleados.show', $empleado) }}" class="btn btn-outline-light btn-sm">Volver</a>
        </x-page-header>

        <form method="GET" action="{{ route('nomina.empleados.certificacion', $empleado) }}" class="card mb-3 d-print-none">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-7">
                    <label for="dirigido_a" class="form-label">Dirigida a</label>
                    <input type="text" id="dirigido_a" name="dirigido_a" class="form-control" maxlength="150" value="{{ $dirigidoA }}">
                </div>
                <div class="col-md-3">
                    <input type="hidden" name="incluir_salario" value="0">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="incluir_salario" name="incluir_salario" value="1" @checked($incluirSalario)>
                        <label class="form-check-label" for="incluir_salario">Incluir salario</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Actualizar</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body p-5">
                @include('nomina.certificaciones.documento', ['paraPdf' => false])
            </div>
        </div>
    </div>
@endsection
