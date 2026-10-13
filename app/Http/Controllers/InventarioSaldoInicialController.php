<?php

namespace App\Http\Controllers;

use App\Models\InventarioSaldoInicial;
use App\Models\Lubricant;
use App\Support\NumberParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Saldos con los que arrancan los kardex de gasolina, ACPM y canastilla. Se cargan una vez,
 * al iniciar; desde ahí el inventario solo cambia con compras y planillas de turno.
 */
class InventarioSaldoInicialController extends Controller
{
    public function index(): View
    {
        $saldos = InventarioSaldoInicial::query()->get()
            ->keyBy(fn (InventarioSaldoInicial $saldo): string => $saldo->inventario.'|'.$saldo->referencia);

        // Referencias del catálogo y las que ya tengan saldo aunque se hayan retirado del catálogo.
        $referencias = Lubricant::query()->orderBy('reference')->pluck('reference')
            ->concat($saldos->where('inventario', InventarioSaldoInicial::CANASTILLA)->pluck('referencia'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('inventarios.saldos-iniciales.index', [
            'saldos' => $saldos,
            'referencias' => $referencias,
            'fechaCorte' => $saldos->min(fn (InventarioSaldoInicial $saldo): string => $saldo->fecha_corte->format('Y-m-d')) ?? now()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'fecha_corte' => ['required', 'date'],
            'saldos' => ['required', 'array'],
            'saldos.*.inventario' => ['required', 'in:'.implode(',', [InventarioSaldoInicial::GASOLINA, InventarioSaldoInicial::ACPM, InventarioSaldoInicial::CANASTILLA])],
            'saldos.*.referencia' => ['nullable', 'string', 'max:150'],
            'saldos.*.unidades' => ['nullable', 'string', 'max:30'],
            'saldos.*.costo_unitario' => ['nullable', 'string', 'max:30'],
        ]);

        $filas = collect($request->input('saldos'))->map(fn (array $fila): array => [
            'inventario' => $fila['inventario'],
            'referencia' => $fila['inventario'] === InventarioSaldoInicial::CANASTILLA ? trim((string) ($fila['referencia'] ?? '')) : '',
            'unidades' => NumberParser::decimal($fila['unidades'] ?? null),
            'costo_unitario' => NumberParser::decimal($fila['costo_unitario'] ?? null),
        ]);

        $errores = $filas
            ->filter(fn (array $fila): bool => $fila['unidades'] < 0 || $fila['costo_unitario'] < 0
                || ($fila['unidades'] > 0 && $fila['costo_unitario'] <= 0))
            ->map(fn (array $fila): string => $this->nombre($fila).': digite una cantidad y un costo unitario mayores que cero.');

        if ($errores->isNotEmpty()) {
            throw ValidationException::withMessages(['saldos' => $errores->values()->all()]);
        }

        DB::transaction(function () use ($filas, $request): void {
            foreach ($filas as $fila) {
                $clave = ['inventario' => $fila['inventario'], 'referencia' => $fila['referencia']];

                // Una fila sin cantidad no tiene saldo inicial: el kardex arranca en cero.
                if ($fila['unidades'] == 0) {
                    InventarioSaldoInicial::query()->where($clave)->delete();

                    continue;
                }

                InventarioSaldoInicial::query()->updateOrCreate($clave, [
                    'fecha_corte' => $request->input('fecha_corte'),
                    'unidades' => $fila['unidades'],
                    'costo_unitario' => $fila['costo_unitario'],
                ]);
            }
        });

        return redirect()->route('inventarios-saldos-iniciales.index')
            ->with('success', 'Saldos iniciales de inventario guardados correctamente.');
    }

    /**
     * @param  array{inventario: string, referencia: string}  $fila
     */
    private function nombre(array $fila): string
    {
        return match ($fila['inventario']) {
            InventarioSaldoInicial::GASOLINA => 'Gasolina',
            InventarioSaldoInicial::ACPM => 'ACPM',
            default => $fila['referencia'],
        };
    }
}
