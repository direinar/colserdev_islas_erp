<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\InventarioAjuste;
use App\Models\InventarioSaldoInicial;
use App\Models\Lubricant;
use App\Support\NumberParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Ajustes de inventario por avería, pérdida, vencimiento, consumo interno o sobrante en un
 * conteo físico. Cada ajuste aparece en su kardex; uno mal digitado se borra y el kardex se
 * recalcula solo.
 */
class InventarioAjusteController extends Controller
{
    use ResuelveRangoFechas;

    /**
     * Inventarios que se pueden ajustar (canastilla por referencia).
     *
     * @var array<string, string>
     */
    public const INVENTARIOS = [
        InventarioSaldoInicial::CANASTILLA => 'CANASTILLA',
        InventarioSaldoInicial::GASOLINA => 'GASOLINA (CORRIENTE)',
        InventarioSaldoInicial::ACPM => 'ACPM',
    ];

    public function index(Request $request): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('inventarios.ajustes.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'ajustes' => InventarioAjuste::query()
                ->with('user')
                ->whereDate('fecha', '>=', $desde)
                ->whereDate('fecha', '<=', $hasta)
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->get(),
            'referencias' => Lubricant::query()->orderBy('reference')->pluck('reference')->filter()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'inventario' => ['required', Rule::in(array_keys(self::INVENTARIOS))],
            'referencia' => ['nullable', 'string', 'max:150', 'required_if:inventario,'.InventarioSaldoInicial::CANASTILLA],
            'motivo' => ['required', Rule::in(array_keys(InventarioAjuste::MOTIVOS))],
            'unidades' => ['required', 'string', 'max:20'],
            'observacion' => ['required', 'string', 'max:500'],
        ], [
            'referencia.required_if' => 'Seleccione el producto de canastilla.',
            'observacion.required' => 'Escriba el motivo o detalle del ajuste.',
        ]);

        $unidades = NumberParser::quantity($validated['unidades']);
        if ($unidades <= 0) {
            throw ValidationException::withMessages(['unidades' => 'Digite una cantidad mayor que cero.']);
        }

        $esCanastilla = $validated['inventario'] === InventarioSaldoInicial::CANASTILLA;

        InventarioAjuste::query()->create([
            'fecha' => $validated['fecha'],
            'inventario' => $validated['inventario'],
            'referencia' => $esCanastilla ? trim((string) $validated['referencia']) : '',
            'motivo' => $validated['motivo'],
            'unidades' => $unidades,
            'observacion' => trim($validated['observacion']),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('inventarios-ajustes.index', $request->only('desde', 'hasta'))
            ->with('success', 'Ajuste de inventario registrado. Ya aparece en el kardex.');
    }

    public function destroy(Request $request, InventarioAjuste $ajuste): RedirectResponse
    {
        $ajuste->delete();

        return redirect()->route('inventarios-ajustes.index', $request->only('desde', 'hasta'))
            ->with('success', 'Ajuste eliminado. El kardex se recalculó sin él.');
    }
}
