<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\ParametroLiquidacion;
use App\Support\NumberParser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'activos');
        $buscar = trim((string) $request->query('buscar', ''));

        $empleados = Empleado::query()
            ->when($estado === 'activos', fn ($query) => $query->where('activo', true))
            ->when($estado === 'retirados', fn ($query) => $query->where('activo', false))
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($query) use ($buscar) {
                    $query->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('nombre_corto', 'like', "%{$buscar}%")
                        ->orWhere('numero_documento', 'like', "%{$buscar}%")
                        ->orWhere('cargo', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();

        return view('nomina.empleados.index', compact('empleados', 'estado', 'buscar'));
    }

    public function create(): View
    {
        $parametros = ParametroLiquidacion::vigenteEn(now());

        return view('nomina.empleados.create', compact('parametros'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empleado = Empleado::create($this->validar($request));

        return redirect()
            ->route('nomina.empleados.show', $empleado)
            ->with('success', 'Empleado creado correctamente.');
    }

    public function show(Empleado $empleado): View
    {
        return view('nomina.empleados.show', compact('empleado'));
    }

    public function edit(Empleado $empleado): View
    {
        $parametros = ParametroLiquidacion::vigenteEn(now());

        return view('nomina.empleados.edit', compact('empleado', 'parametros'));
    }

    public function update(Request $request, Empleado $empleado): RedirectResponse
    {
        $empleado->update($this->validar($request, $empleado));

        return redirect()
            ->route('nomina.empleados.show', $empleado)
            ->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy(Empleado $empleado): RedirectResponse
    {
        if ($empleado->asignacionesTurno()->exists()) {
            return redirect()
                ->route('nomina.empleados.index')
                ->with('error', 'El empleado tiene turnos en planillas: márquelo como retirado en lugar de eliminarlo.');
        }

        $empleado->delete();

        return redirect()
            ->route('nomina.empleados.index')
            ->with('success', 'Empleado eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Empleado $empleado = null): array
    {
        $request->merge([
            'salario_basico' => NumberParser::money($request->input('salario_basico')),
            'recibe_auxilio_transporte' => $request->boolean('recibe_auxilio_transporte'),
            'aplica_recargos' => $request->boolean('aplica_recargos'),
            'activo' => $request->boolean('activo'),
            'nombre_corto' => mb_strtoupper(trim((string) $request->input('nombre_corto'))),
        ]);

        $validated = $request->validate([
            'tipo_documento' => ['required', Rule::in(array_keys(Empleado::tiposDocumento()))],
            'numero_documento' => ['required', 'string', 'max:20', Rule::unique('nomina_empleados', 'numero_documento')->ignore($empleado?->id)],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'nombre_corto' => ['required', 'string', 'max:30'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'cargo' => ['required', 'string', 'max:255'],
            'fecha_ingreso' => ['required', 'date'],
            'tipo_contrato' => ['required', Rule::in(array_keys(Empleado::tiposContrato()))],
            'fecha_vencimiento_contrato' => [
                Rule::requiredIf(fn () => in_array($request->input('tipo_contrato'), ['fijo', 'aprendizaje'], true)),
                'nullable', 'date', 'after:fecha_ingreso',
            ],
            'salario_basico' => ['required', 'numeric', 'gt:0'],
            'recibe_auxilio_transporte' => ['boolean'],
            'aplica_recargos' => ['boolean'],
            'eps' => ['nullable', 'string', 'max:255'],
            'fondo_pension' => ['nullable', 'string', 'max:255'],
            'fondo_cesantias' => ['nullable', 'string', 'max:255'],
            'forma_pago' => ['required', Rule::in(array_keys(Empleado::formasPago()))],
            'banco' => ['nullable', 'required_if:forma_pago,transferencia', 'string', 'max:255'],
            'tipo_cuenta' => ['nullable', 'required_if:forma_pago,transferencia', Rule::in(array_keys(Empleado::tiposCuenta()))],
            'numero_cuenta' => ['nullable', 'required_if:forma_pago,transferencia', 'string', 'max:30'],
            'activo' => ['boolean'],
            'fecha_retiro' => ['nullable', 'required_if:activo,false', 'date', 'after_or_equal:fecha_ingreso'],
            'motivo_retiro' => ['nullable', 'string', 'max:255'],
        ], [
            'fecha_vencimiento_contrato.required' => 'Los contratos a término fijo y de aprendizaje deben tener fecha de vencimiento.',
            'banco.required_if' => 'Indique el banco para pagar por transferencia.',
            'tipo_cuenta.required_if' => 'Indique el tipo de cuenta para pagar por transferencia.',
            'numero_cuenta.required_if' => 'Indique el número de cuenta para pagar por transferencia.',
            'fecha_retiro.required_if' => 'Indique la fecha de retiro del empleado inactivo.',
        ], [
            'numero_documento' => 'número de documento',
            'nombre_corto' => 'nombre corto',
            'fecha_ingreso' => 'fecha de ingreso',
            'tipo_contrato' => 'tipo de contrato',
            'fecha_vencimiento_contrato' => 'vencimiento del contrato',
            'salario_basico' => 'sueldo básico',
            'forma_pago' => 'forma de pago',
        ]);

        if ($validated['activo']) {
            $validated['fecha_retiro'] = null;
            $validated['motivo_retiro'] = null;
        }

        if ($validated['forma_pago'] === 'efectivo') {
            $validated['banco'] = null;
            $validated['tipo_cuenta'] = null;
            $validated['numero_cuenta'] = null;
        }

        return $validated;
    }
}
