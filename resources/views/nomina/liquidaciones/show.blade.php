@extends('layouts.app')

@section('title', 'Nómina '.$liquidacion->nombreDelMes())

@php
    $dinero = fn (float $valor): string => '$ '.number_format($valor, 0, ',', '.');
    $valorOVacio = fn (float $valor): string => round($valor, 2) == 0.0 ? '' : number_format($valor, 0, ',', '.');
    $numero = fn (float $valor): string => rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
    $editable = ! $liquidacion->estaAprobada();
    $filas = $liquidacion->empleados;
    $parametros = $liquidacion->parametros;
    $porcentaje = fn (?string $valor): string => rtrim(rtrim(number_format((float) $valor, 3, ',', '.'), '0'), ',').'%';

    // Hoja NOMINA: [etiqueta, campo o closure, tipo de fila].
    $conceptos = [
        ['DEVENGADOS', null, 'seccion'],
        ['BÁSICO', 'basico', ''],
        ['VACACIONES', 'vacaciones', ''],
        ['RECARGO NOCTURNO ('.$porcentaje($parametros?->porcentaje_recargo_nocturno).')', 'recargo_nocturno', ''],
        ['RECARGO DOMINICAL Y FESTIVO ('.$porcentaje($parametros?->porcentaje_recargo_festivo).')', 'recargo_festivo', ''],
        ['SUBTOTAL RECARGOS', fn ($fila) => $fila->recargo_nocturno + $fila->recargo_festivo, 'subtotal'],
        ['BASE APORTES PARAFISCALES', 'base_aportes', 'subtotal'],
        ['AUXILIO DE TRANSPORTE', 'auxilio_transporte', ''],
        ['CESANTÍAS', 'cesantias', ''],
        ['INTERESES S/CESANTÍAS', 'intereses_cesantias', ''],
        ['PRIMA', 'prima', ''],
        ['TOTAL DEVENGADO', 'total_devengado', 'total'],
        ['DEDUCCIONES', null, 'seccion'],
        ['SALUD ('.$porcentaje($parametros?->porcentaje_salud_empleado).')', 'salud', ''],
        ['PENSIÓN ('.$porcentaje($parametros?->porcentaje_pension_empleado).')', 'pension', ''],
        ['SUBTOTAL SALUD Y PENSIÓN', fn ($fila) => $fila->salud + $fila->pension, 'subtotal'],
        ['ANTICIPOS', 'anticipos', ''],
        ['FALTANTES', 'faltantes', ''],
        ['SUBTOTAL OTROS DESCUENTOS', fn ($fila) => $fila->anticipos + $fila->faltantes, 'subtotal'],
        ['TOTAL DEDUCCIONES', 'total_deducciones', 'subtotal'],
        ['NETO A PAGAR POR EMPLEADO', 'neto_pagar', 'total'],
        ['APORTES DEL EMPLEADOR', null, 'seccion'],
        ['PENSIÓN ('.$porcentaje($parametros?->porcentaje_pension_empleador).')', 'aporte_pension', ''],
        ['SALUD ('.$porcentaje($parametros?->porcentaje_salud_empleador).')', 'aporte_salud', ''],
        ['RIESGOS LABORALES ('.$porcentaje($parametros?->porcentaje_riesgos_laborales).')', 'aporte_riesgos', ''],
        ['CAJA DE COMPENSACIÓN ('.$porcentaje($parametros?->porcentaje_caja_compensacion).')', 'aporte_caja', ''],
        ['TOTAL APORTES', fn ($fila) => $fila->totalAportes(), 'subtotal'],
        ['PROVISIONES PRESTACIONES SOCIALES', null, 'seccion'],
        ['CESANTÍAS ('.$porcentaje($parametros?->porcentaje_provision_cesantias).')', 'provision_cesantias', ''],
        ['INTERESES S/CESANTÍAS ('.$porcentaje($parametros?->porcentaje_provision_intereses_cesantias).')', 'provision_intereses', ''],
        ['VACACIONES ('.$porcentaje($parametros?->porcentaje_provision_vacaciones).')', 'provision_vacaciones', ''],
        ['PRIMA LEGAL ('.$porcentaje($parametros?->porcentaje_provision_prima).')', 'provision_prima', ''],
        ['TOTAL PROVISIONES', fn ($fila) => $fila->totalProvisiones(), 'subtotal'],
        ['COSTO TOTAL DE LA NÓMINA', 'costo_total', 'total'],
    ];
    $valor = fn ($fila, $campo): float => (float) (is_callable($campo) ? $campo($fila) : $fila->{$campo});
@endphp

@section('content')
    @include('nomina.partials.estilos')

    <x-page-header :title="'Nómina · '.$liquidacion->nombreDelMes()" icon="cash-coin"
        :subtitle="'Parámetros: '.($parametros?->descripcion ?? 'sin vigencia').' · Hora ordinaria $'.number_format($parametros?->valorHoraOrdinaria() ?? 0, 0, ',', '.').' · Auxilio de transporte $'.number_format((float) $parametros?->auxilio_transporte, 0, ',', '.')">
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
        <a href="{{ route('nomina.nominas.desprendibles', $liquidacion) }}" class="btn btn-outline-light btn-sm" target="_blank">
            <i class="bi bi-receipt" aria-hidden="true"></i> Desprendibles
        </a>
        @if ($editable)
            <form method="POST" action="{{ route('nomina.nominas.aprobar', $liquidacion) }}" class="d-inline" data-sin-aviso
                onsubmit="return confirm('¿Aprobar la nómina de {{ $liquidacion->nombreDelMes() }}? Quedará fija y alimentará los acumulados.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-check2-circle" aria-hidden="true"></i> Aprobar nómina</button>
            </form>
        @else
            <form method="POST" action="{{ route('nomina.nominas.reabrir', $liquidacion) }}" class="d-inline" data-sin-aviso
                onsubmit="return confirm('¿Devolver la nómina a borrador para modificarla?');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-light btn-sm"><i class="bi bi-unlock" aria-hidden="true"></i> Reabrir</button>
            </form>
        @endif
        <a href="{{ route('nomina.nominas.index') }}" class="btn btn-outline-light btn-sm">Volver</a>
    </x-page-header>

    @if (session('error'))
        <div class="alert alert-danger d-print-none">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif
    @include('nomina.partials.errores-formulario')

    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <span class="nm-estado {{ $liquidacion->estado }}">{{ $liquidacion->estaAprobada() ? 'APROBADA' : 'BORRADOR' }}</span>
        @if ($liquidacion->estaAprobada())
            <span class="small text-muted">por {{ $liquidacion->aprobada_por }} el {{ $liquidacion->aprobada_at?->format('d/m/Y H:i') }}. Para corregirla use «Reabrir».</span>
        @else
            <span class="small text-muted">Digite las novedades, guarde y revise. Al aprobar, la nómina queda fija y alimenta los acumulados y certificados.</span>
        @endif
    </div>

    <div class="nm-kpis">
        <div class="nm-kpi nm-azul"><span class="nm-kpi-icono"><i class="bi bi-people" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">{{ $filas->count() }}</span><span class="nm-kpi-titulo">Empleados</span></span></div>
        <div class="nm-kpi nm-verde"><span class="nm-kpi-icono"><i class="bi bi-plus-circle" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">{{ $dinero($totales['total_devengado']) }}</span><span class="nm-kpi-titulo">Total devengado</span></span></div>
        <div class="nm-kpi nm-rojo"><span class="nm-kpi-icono"><i class="bi bi-dash-circle" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">{{ $dinero($totales['total_deducciones']) }}</span><span class="nm-kpi-titulo">Total deducciones</span></span></div>
        <div class="nm-kpi nm-azul"><span class="nm-kpi-icono"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block" id="kpi-neto">{{ $dinero($totales['neto_pagar']) }}</span><span class="nm-kpi-titulo">Neto a pagar</span></span></div>
        <div class="nm-kpi nm-morado"><span class="nm-kpi-icono"><i class="bi bi-building" aria-hidden="true"></i></span>
            <span><span class="nm-kpi-valor d-block">{{ $dinero($totales['costo_total']) }}</span><span class="nm-kpi-titulo">Costo total (con aportes y provisiones)</span></span></div>
    </div>

    @if ($alertas)
        <div class="alert alert-warning small d-print-none" id="alertas-nomina">
            <strong><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Revise antes de aprobar:</strong>
            <ul class="mb-0">
                @foreach ($alertas as $alerta)
                    <li>{{ $alerta }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <ul class="nav nav-tabs nm-tabs mb-3" role="tablist">
        @foreach (['novedades' => ['pencil-square', 'Novedades'], 'nomina' => ['table', 'Nómina'], 'pago' => ['bank', 'Forma de pago'], 'contable' => ['journal-text', 'Contabilización'], 'desprendibles' => ['receipt', 'Desprendibles']] as $clave => [$icono, $nombre])
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="pestana-{{ $clave }}" data-bs-toggle="tab" data-bs-target="#panel-{{ $clave }}"
                    type="button" role="tab" aria-controls="panel-{{ $clave }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    <i class="bi bi-{{ $icono }}" aria-hidden="true"></i> {{ $nombre }}
                    @if ($clave === 'pago' && round($formaPago['diferencia'], 2) != 0.0)
                        <span class="badge text-bg-danger">!</span>
                    @endif
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        {{-- NOVEDADES --}}
        <div class="tab-pane fade show active" id="panel-novedades" role="tabpanel" aria-labelledby="pestana-novedades">
            <form method="POST" action="{{ route('nomina.nominas.update', $liquidacion) }}" id="form-novedades">
                @csrf
                @method('PUT')
                @if ($editable)
                    <div class="d-flex flex-wrap gap-2 mb-2 nm-acciones">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-calculator" aria-hidden="true"></i> Guardar y calcular</button>
                        <button type="submit" form="form-recargos" class="btn btn-outline-primary"
                            title="{{ $planilla ? 'Planilla '.($planilla->estaCerrada() ? 'cerrada' : 'en borrador') : 'No existe la planilla del mes' }}" @disabled(! $planilla)>
                            <i class="bi bi-moon-stars" aria-hidden="true"></i> Traer recargos de la planilla
                        </button>
                        <button type="submit" form="form-sincronizar" class="btn btn-outline-secondary">
                            <i class="bi bi-person-plus" aria-hidden="true"></i> Agregar empleados faltantes
                        </button>
                    </div>
                @endif
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm align-middle nm-tabla nm-novedades mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="col-fija">EMPLEADO</th>
                                <th class="text-center" title="Días a liquidar (base 30)">DÍAS</th>
                                <th class="text-center">VAC. DISFRUT. (días)</th>
                                <th class="text-center">VAC. DINERO (días)</th>
                                <th class="text-center">H. NOCTURNAS</th>
                                <th class="text-center">H. FESTIVAS</th>
                                <th class="text-center">CESANTÍAS</th>
                                <th class="text-center">INTERESES</th>
                                <th class="text-center">PRIMA</th>
                                <th class="text-center">ANTICIPOS</th>
                                <th class="text-center">FALTANTES</th>
                                <th>MEDIO DE PAGO</th>
                                <th class="num">NETO A PAGAR</th>
                                @if ($editable)
                                    <th class="d-print-none"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($filas as $fila)
                                @php($nombre = 'filas['.$fila->id.']')
                                <tr>
                                    <td class="col-fija empleado">
                                        <strong>{{ $fila->empleado->nombreParaNomina() }}</strong>
                                        <small>{{ $fila->empleado->cargo }} · {{ $dinero($fila->salario_basico) }}
                                            @if ($fila->recibe_auxilio_transporte) · Aux. @endif
                                            @if ($fila->aplica_recargos) · Recargos @endif
                                        </small>
                                    </td>
                                    @if ($editable)
                                        <td><input type="number" class="form-control" name="{{ $nombre }}[dias_trabajados]" value="{{ old($nombre.'.dias_trabajados', $fila->dias_trabajados) }}" min="0" max="30" required aria-label="Días de {{ $fila->empleado->nombreParaNomina() }}"></td>
                                        <td><input type="number" class="form-control" name="{{ $nombre }}[dias_vacaciones_disfrutadas]" value="{{ $fila->dias_vacaciones_disfrutadas ?: '' }}" min="0" max="30" aria-label="Vacaciones disfrutadas"></td>
                                        <td><input type="number" class="form-control" name="{{ $nombre }}[dias_vacaciones_dinero]" value="{{ $fila->dias_vacaciones_dinero ?: '' }}" min="0" max="90" aria-label="Vacaciones en dinero"></td>
                                        <td><input type="number" class="form-control" name="{{ $nombre }}[horas_nocturnas]" value="{{ $fila->horas_nocturnas ?: '' }}" min="0" step="0.01" @disabled(! $fila->aplica_recargos) aria-label="Horas nocturnas"></td>
                                        <td><input type="number" class="form-control" name="{{ $nombre }}[horas_festivas]" value="{{ $fila->horas_festivas ?: '' }}" min="0" step="0.01" @disabled(! $fila->aplica_recargos) aria-label="Horas festivas"></td>
                                        @foreach (['cesantias', 'intereses_cesantias', 'prima', 'anticipos', 'faltantes'] as $campo)
                                            <td><input type="number" class="form-control dinero" name="{{ $nombre }}[{{ $campo }}]" value="{{ $fila->{$campo} ? round($fila->{$campo}) : '' }}" min="0" step="1" aria-label="{{ $campo }}"></td>
                                        @endforeach
                                        <td>
                                            <select class="form-select" name="{{ $nombre }}[medio_pago]" aria-label="Medio de pago">
                                                @foreach (collect($mediosPago)->push($fila->medio_pago)->filter()->unique() as $medio)
                                                    <option value="{{ $medio }}" @selected($fila->medio_pago === $medio)>{{ $medio }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @else
                                        <td class="num">{{ $fila->dias_trabajados }}</td>
                                        <td class="num">{{ $fila->dias_vacaciones_disfrutadas ?: '' }}</td>
                                        <td class="num">{{ $fila->dias_vacaciones_dinero ?: '' }}</td>
                                        <td class="num">{{ $fila->horas_nocturnas ? $numero($fila->horas_nocturnas) : '' }}</td>
                                        <td class="num">{{ $fila->horas_festivas ? $numero($fila->horas_festivas) : '' }}</td>
                                        @foreach (['cesantias', 'intereses_cesantias', 'prima', 'anticipos', 'faltantes'] as $campo)
                                            <td class="num">{{ $valorOVacio($fila->{$campo}) }}</td>
                                        @endforeach
                                        <td>{{ $fila->medio_pago }}</td>
                                    @endif
                                    <td class="num fw-bold {{ $fila->neto_pagar < 0 ? 'text-danger' : '' }}">{{ $dinero($fila->neto_pagar) }}</td>
                                    @if ($editable)
                                        <td class="text-center d-print-none">
                                            <button type="submit" form="quitar-{{ $fila->id }}" class="btn btn-sm btn-outline-danger" title="Quitar de esta nómina"
                                                aria-label="Quitar a {{ $fila->empleado->nombreParaNomina() }}">
                                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center text-muted py-4">No hay empleados vinculados en el mes. Regístrelos en Empleados y use «Agregar empleados faltantes».</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($filas->isNotEmpty())
                            <tfoot>
                                <tr class="fila-total">
                                    <td class="col-fija">TOTALES</td>
                                    <td colspan="3"></td>
                                    <td class="num">{{ $numero($totales['horas_nocturnas']) }}</td>
                                    <td class="num">{{ $numero($totales['horas_festivas']) }}</td>
                                    @foreach (['cesantias', 'intereses_cesantias', 'prima', 'anticipos', 'faltantes'] as $campo)
                                        <td class="num">{{ $valorOVacio($totales[$campo]) }}</td>
                                    @endforeach
                                    <td></td>
                                    <td class="num">{{ $dinero($totales['neto_pagar']) }}</td>
                                    @if ($editable)
                                        <td class="d-print-none"></td>
                                    @endif
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <p class="small text-muted mb-2">
                    <strong>DÍAS</strong>: días a liquidar sobre base 30 (sin contar las vacaciones disfrutadas). Las horas nocturnas y festivas vienen de la
                    planilla de turnos{{ $planilla ? ' de '.$planilla->nombreDelMes().' ('.($planilla->estaCerrada() ? 'cerrada' : 'en borrador').')' : '' }}.
                    Cesantías, intereses y prima se digitan solo el mes en que se pagan.
                </p>
                <label for="observaciones" class="form-label small fw-semibold">Observaciones de la nómina</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="2" @disabled(! $editable)>{{ old('observaciones', $liquidacion->observaciones) }}</textarea>
                @if ($editable)
                    <button type="submit" class="btn btn-primary mt-2 nm-acciones"><i class="bi bi-calculator" aria-hidden="true"></i> Guardar y calcular</button>
                @endif
            </form>
        </div>

        {{-- NÓMINA (como la hoja NOMINA: conceptos por filas, empleados por columnas) --}}
        <div class="tab-pane fade" id="panel-nomina" role="tabpanel" aria-labelledby="pestana-nomina">
            <div class="table-responsive">
                <table class="table table-bordered table-sm nm-tabla" id="tabla-nomina">
                    <thead class="table-primary">
                        <tr>
                            <th class="col-fija">NÓMINA DEL MES DE {{ $liquidacion->nombreDelMes() }}</th>
                            @foreach ($filas as $fila)
                                <th class="num">{{ $fila->empleado->nombreParaNomina() }}</th>
                            @endforeach
                            <th class="num">TOTALES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conceptos as [$etiqueta, $campo, $tipo])
                            @if ($tipo === 'seccion')
                                <tr class="fila-seccion"><td colspan="{{ $filas->count() + 2 }}">{{ $etiqueta }}</td></tr>
                            @else
                                <tr @class(['fila-subtotal' => $tipo === 'subtotal', 'fila-total' => $tipo === 'total'])>
                                    <td class="col-fija">{{ $etiqueta }}</td>
                                    @foreach ($filas as $fila)
                                        <td class="num">{{ $valorOVacio($valor($fila, $campo)) }}</td>
                                    @endforeach
                                    <td class="num">{{ $valorOVacio($filas->sum(fn ($fila) => $valor($fila, $campo))) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- FORMA DE PAGO --}}
        <div class="tab-pane fade" id="panel-pago" role="tabpanel" aria-labelledby="pestana-pago">
            <div class="row g-2 mb-3">
                @foreach ($formaPago['medios'] as $medio)
                    <div class="col-sm-6 col-lg-3">
                        <div class="nm-pago">
                            <div class="small text-muted text-uppercase"><i class="bi bi-{{ $medio['medio'] === 'Efectivo' ? 'cash-stack' : 'bank' }}" aria-hidden="true"></i> {{ $medio['medio'] }}</div>
                            <div class="nm-pago-valor">{{ $dinero($medio['total']) }}</div>
                            <div class="small text-muted">{{ $medio['empleados'] }} empleado(s)</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="row g-3">
                <div class="col-lg-6">
                    <table class="table table-bordered table-sm nm-tabla">
                        <thead class="table-primary">
                            <tr><th colspan="3">FORMA DE PAGO DE LA PRESENTE NÓMINA</th></tr>
                            <tr><th>BANCO / MEDIO</th><th class="num">EMPLEADOS</th><th class="num">TRANSFERENCIA / PAGO</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($formaPago['medios'] as $medio)
                                <tr><td>{{ $medio['medio'] }}</td><td class="num">{{ $medio['empleados'] }}</td><td class="num">{{ $dinero($medio['total']) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fila-total"><td colspan="2">TOTAL PAGOS</td><td class="num">{{ $dinero($formaPago['total']) }}</td></tr>
                            <tr class="fila-subtotal"><td colspan="2">NETO A PAGAR DE LA NÓMINA</td><td class="num">{{ $dinero($formaPago['neto']) }}</td></tr>
                            <tr>
                                <td colspan="2" class="fw-bold">DIFERENCIA</td>
                                <td class="num fw-bold {{ round($formaPago['diferencia'], 2) == 0.0 ? 'text-success' : 'text-danger' }}">
                                    {{ $dinero($formaPago['diferencia']) }} {{ round($formaPago['diferencia'], 2) == 0.0 ? '✔ Cuadra' : '' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="col-lg-6">
                    <table class="table table-bordered table-sm nm-tabla">
                        <thead class="table-light">
                            <tr><th>EMPLEADO</th><th>MEDIO</th><th>CUENTA</th><th class="num">NETO</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($filas->sortBy('medio_pago') as $fila)
                                <tr>
                                    <td>{{ $fila->empleado->nombre_completo }}</td>
                                    <td>{{ $fila->medio_pago }}</td>
                                    <td class="small">{{ $fila->medio_pago === 'Efectivo' ? '' : trim(($fila->empleado->tipo_cuenta ? ucfirst($fila->empleado->tipo_cuenta).' ' : '').$fila->empleado->numero_cuenta) }}</td>
                                    <td class="num">{{ $dinero($fila->neto_pagar) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="small text-muted">El medio de pago de cada empleado se cambia en la pestaña Novedades. Por defecto es el banco de su ficha.</p>
                </div>
            </div>
        </div>

        {{-- CONTABILIZACIÓN --}}
        <div class="tab-pane fade" id="panel-contable" role="tabpanel" aria-labelledby="pestana-contable">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <span>
                    @if (round($contabilizacion['debito'] - $contabilizacion['credito'], 2) == 0.0)
                        <span class="badge text-bg-success"><i class="bi bi-check-circle" aria-hidden="true"></i> Comprobante cuadrado</span>
                    @else
                        <span class="badge text-bg-danger">Descuadre de {{ $dinero($contabilizacion['debito'] - $contabilizacion['credito']) }}</span>
                    @endif
                </span>
                <a href="{{ route('nomina.cuentas-contables.index') }}" class="btn btn-sm btn-outline-primary nm-acciones"><i class="bi bi-gear" aria-hidden="true"></i> Cuentas contables</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm nm-tabla">
                    <thead class="table-primary">
                        <tr><th>No. CUENTA</th><th>CONCEPTO</th><th>TERCERO</th><th>IDENTIFICACIÓN</th><th class="num">DÉBITO</th><th class="num">CRÉDITO</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($contabilizacion['lineas'] as $linea)
                            <tr>
                                <td>{!! $linea['cuenta'] ? e($linea['cuenta']) : '<span class="text-danger small">Sin cuenta</span>' !!}</td>
                                <td>{{ $linea['concepto'] }}</td>
                                <td>{{ $linea['tercero'] }}</td>
                                <td>{{ $linea['identificacion'] }}</td>
                                <td class="num">{{ $valorOVacio($linea['debito']) }}</td>
                                <td class="num">{{ $valorOVacio($linea['credito']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Sin movimientos.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fila-total"><td colspan="4">SUMAS IGUALES</td><td class="num">{{ $dinero($contabilizacion['debito']) }}</td><td class="num">{{ $dinero($contabilizacion['credito']) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- DESPRENDIBLES --}}
        <div class="tab-pane fade" id="panel-desprendibles" role="tabpanel" aria-labelledby="pestana-desprendibles">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="{{ route('nomina.nominas.desprendibles', $liquidacion) }}" class="btn btn-primary" target="_blank"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir todos</a>
                <a href="{{ route('nomina.nominas.desprendibles.pdf', $liquidacion) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF de todos</a>
            </div>
            @if (! $liquidacion->estaAprobada())
                <p class="small text-warning-emphasis"><i class="bi bi-info-circle" aria-hidden="true"></i> La nómina está en borrador: los desprendibles salen marcados como BORRADOR hasta aprobarla.</p>
            @endif
            <div class="row g-2">
                @foreach ($filas as $fila)
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <div class="nm-pago d-flex justify-content-between align-items-center gap-2">
                            <span>
                                <strong class="d-block">{{ $fila->empleado->nombre_completo }}</strong>
                                <span class="small text-muted">Neto {{ $dinero($fila->neto_pagar) }}</span>
                            </span>
                            <span class="text-nowrap">
                                <a href="{{ route('nomina.nominas.desprendibles', [$liquidacion, 'fila' => $fila->id]) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Imprimir"><i class="bi bi-printer" aria-hidden="true"></i></a>
                                <a href="{{ route('nomina.nominas.desprendibles.pdf', [$liquidacion, 'fila' => $fila->id]) }}" class="btn btn-sm btn-outline-danger" title="PDF"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></a>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Formularios auxiliares (fuera del formulario de novedades). --}}
    @if ($editable)
        <form method="POST" action="{{ route('nomina.nominas.recargos', $liquidacion) }}" id="form-recargos" data-sin-aviso
            onsubmit="return confirm('¿Reemplazar las horas nocturnas y festivas por las de la planilla de turnos? Guarde antes las demás novedades.');">@csrf</form>
        <form method="POST" action="{{ route('nomina.nominas.sincronizar', $liquidacion) }}" id="form-sincronizar" data-sin-aviso>@csrf</form>
        @foreach ($filas as $fila)
            <form method="POST" action="{{ route('nomina.nominas.quitar-empleado', [$liquidacion, $fila]) }}" id="quitar-{{ $fila->id }}" data-sin-aviso
                onsubmit="return confirm('¿Quitar a {{ $fila->empleado->nombreParaNomina() }} de esta nómina?');">@csrf @method('DELETE')</form>
        @endforeach
    @endif

    <script>
        // Recuerda la pestaña abierta al recargar (p. ej. después de guardar).
        document.addEventListener('DOMContentLoaded', function () {
            const clave = 'nomina-pestana-{{ $liquidacion->id }}';
            try {
                const guardada = sessionStorage.getItem(clave);
                if (guardada && document.getElementById(guardada)) {
                    document.getElementById(guardada).click();
                }
            } catch (e) {}
            document.querySelectorAll('.nm-tabs [data-bs-toggle="tab"]').forEach((boton) => boton.addEventListener('shown.bs.tab', function () {
                try { sessionStorage.setItem(clave, boton.id); } catch (e) {}
            }));
        });
    </script>
@endsection
