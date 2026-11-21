@extends('layouts.app')

@section('title', $empleado->nombre_completo)

@section('content')
    <div class="container">

        <x-page-header :title="$empleado->nombre_completo" :subtitle="$empleado->cargo" icon="person-vcard">
            <a href="{{ route('nomina.empleados.edit', $empleado) }}" class="btn btn-light">Editar</a>
            <div class="dropdown d-inline-block">
                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Documentos</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('nomina.empleados.certificacion', $empleado) }}"><i class="bi bi-award" aria-hidden="true"></i> Certificación laboral</a></li>
                    <li><a class="dropdown-item" href="{{ route('nomina.vacaciones.show', $empleado) }}"><i class="bi bi-sun" aria-hidden="true"></i> Control de vacaciones</a></li>
                    <li><a class="dropdown-item" href="{{ route('nomina.acumulados.certificado', $empleado) }}"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Certificado de ingresos</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('nomina.liquidaciones-contrato.create', ['empleado' => $empleado->id]) }}"><i class="bi bi-file-earmark-person" aria-hidden="true"></i> Liquidar contrato</a></li>
                </ul>
            </div>
            <a href="{{ route('nomina.empleados.index') }}" class="btn btn-light">Volver</a>
        </x-page-header>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($empleado->contratoPorVencer())
            <div class="alert alert-warning">
                El contrato vence el {{ $empleado->fecha_vencimiento_contrato->format('d/m/Y') }}.
            </div>
        @endif

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Datos personales</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Documento</dt>
                            <dd class="col-sm-7">{{ App\Models\Nomina\Empleado::tiposDocumento()[$empleado->tipo_documento] ?? $empleado->tipo_documento }} {{ $empleado->numero_documento }}</dd>
                            <dt class="col-sm-5">Nombre corto</dt>
                            <dd class="col-sm-7">{{ $empleado->nombre_corto }}</dd>
                            <dt class="col-sm-5">Teléfono</dt>
                            <dd class="col-sm-7">{{ $empleado->telefono ?: '—' }}</dd>
                            <dt class="col-sm-5">Correo</dt>
                            <dd class="col-sm-7">{{ $empleado->correo ?: '—' }}</dd>
                            <dt class="col-sm-5">Dirección</dt>
                            <dd class="col-sm-7">{{ $empleado->direccion ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Contrato y salario</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Fecha de ingreso</dt>
                            <dd class="col-sm-7">{{ $empleado->fecha_ingreso->format('d/m/Y') }}</dd>
                            <dt class="col-sm-5">Tipo de contrato</dt>
                            <dd class="col-sm-7">{{ App\Models\Nomina\Empleado::tiposContrato()[$empleado->tipo_contrato] ?? $empleado->tipo_contrato }}</dd>
                            <dt class="col-sm-5">Vencimiento</dt>
                            <dd class="col-sm-7">{{ $empleado->fecha_vencimiento_contrato?->format('d/m/Y') ?? 'Indefinido' }}</dd>
                            <dt class="col-sm-5">Sueldo básico</dt>
                            <dd class="col-sm-7">$ {{ number_format((float) $empleado->salario_basico, 0, ',', '.') }}</dd>
                            <dt class="col-sm-5">Auxilio de transporte</dt>
                            <dd class="col-sm-7">{{ $empleado->recibe_auxilio_transporte ? 'Sí' : 'No' }}</dd>
                            <dt class="col-sm-5">Sobresueldos y recargos</dt>
                            <dd class="col-sm-7">{{ $empleado->aplica_recargos ? 'Sí' : 'No' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Seguridad social</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">EPS</dt>
                            <dd class="col-sm-7">{{ $empleado->eps ?: '—' }}</dd>
                            <dt class="col-sm-5">Fondo de pensiones</dt>
                            <dd class="col-sm-7">{{ $empleado->fondo_pension ?: '—' }}</dd>
                            <dt class="col-sm-5">Fondo de cesantías</dt>
                            <dd class="col-sm-7">{{ $empleado->fondo_cesantias ?: '—' }}</dd>
                            <dt class="col-sm-5">ARL</dt>
                            <dd class="col-sm-7">{{ $empleado->arl ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Pago y estado</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Forma de pago</dt>
                            <dd class="col-sm-7">{{ App\Models\Nomina\Empleado::formasPago()[$empleado->forma_pago] ?? $empleado->forma_pago }}</dd>
                            @if ($empleado->forma_pago === 'transferencia')
                                <dt class="col-sm-5">Cuenta</dt>
                                <dd class="col-sm-7">{{ $empleado->banco }} · {{ App\Models\Nomina\Empleado::tiposCuenta()[$empleado->tipo_cuenta] ?? '' }} {{ $empleado->numero_cuenta }}</dd>
                            @endif
                            <dt class="col-sm-5">Estado</dt>
                            <dd class="col-sm-7">{{ $empleado->activo ? 'Activo' : 'Retirado' }}</dd>
                            @unless ($empleado->activo)
                                <dt class="col-sm-5">Fecha de retiro</dt>
                                <dd class="col-sm-7">{{ $empleado->fecha_retiro?->format('d/m/Y') }}</dd>
                                <dt class="col-sm-5">Motivo</dt>
                                <dd class="col-sm-7">{{ $empleado->motivo_retiro ?: '—' }}</dd>
                            @endunless
                        </dl>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
