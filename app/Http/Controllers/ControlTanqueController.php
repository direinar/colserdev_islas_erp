<?php

namespace App\Http\Controllers;

use App\Models\AforoTanque;
use App\Models\TanqueSaldoInicial;
use App\Services\ControlTanquesExcelService;
use App\Services\ControlTanquesService;
use App\Support\DescargaExcel;
use App\Support\NumberParser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ControlTanqueController extends Controller
{
    /**
     * Un informe muy largo (un año ya son 365 filas) se vuelve inmanejable en pantalla.
     */
    private const MAXIMO_DIAS = 400;

    public function index(Request $request, ControlTanquesService $controlTanques): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('control-tanques.index', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $controlTanques->informe($desde, $hasta));
    }

    /**
     * El informe en PDF (horizontal), para descargarlo o compartirlo por WhatsApp desde el módulo.
     */
    public function pdf(Request $request, ControlTanquesService $controlTanques): Response
    {
        [$desde, $hasta] = $this->rango($request);

        return Pdf::loadView('control-tanques.pdf', [
            'desde' => $desde,
            'hasta' => $hasta,
        ] + $controlTanques->informe($desde, $hasta))
            ->setPaper('letter', 'landscape')
            ->download('control-de-tanques-'.$desde.'-a-'.$hasta.'.pdf');
    }

    /**
     * Guarda el aforo nocturno de todas las fechas visibles. Una fecha sin ningún dato
     * borra su aforo, para poder corregir una medición digitada en el día equivocado.
     */
    public function guardarAforos(Request $request): RedirectResponse
    {
        $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date'],
            'aforos' => ['array'],
            'aforos.*.vara_corriente' => ['nullable', 'string', 'max:20'],
            'aforos.*.tabla_corriente' => ['nullable', 'string', 'max:20'],
            'aforos.*.vara_acpm' => ['nullable', 'string', 'max:20'],
            'aforos.*.tabla_acpm' => ['nullable', 'string', 'max:20'],
        ]);

        DB::transaction(function () use ($request): void {
            foreach ($request->input('aforos', []) as $fecha => $row) {
                if (! $this->esFecha((string) $fecha)) {
                    continue;
                }

                $valores = collect(['vara_corriente', 'tabla_corriente', 'vara_acpm', 'tabla_acpm'])
                    ->mapWithKeys(fn (string $campo): array => [
                        $campo => trim((string) ($row[$campo] ?? '')) === ''
                            ? null
                            : NumberParser::quantity($row[$campo]),
                    ]);

                $aforo = AforoTanque::query()->whereDate('fecha', $fecha)->first();

                if ($valores->filter(fn (?float $valor): bool => $valor !== null)->isEmpty()) {
                    $aforo?->delete();

                    continue;
                }

                if ($aforo === null) {
                    $aforo = new AforoTanque(['fecha' => $fecha]);
                }

                $aforo->fill($valores->all() + ['user_id' => $request->user()->id])->save();
            }
        });

        return redirect()->route('control-tanques.index', $request->only('desde', 'hasta'))
            ->with('success', 'Aforos guardados correctamente.');
    }

    public function guardarSaldoInicial(Request $request): RedirectResponse
    {
        // Un solo formulario: una fecha de corte para los dos tanques.
        $request->validate([
            'fecha_corte' => ['required', 'date'],
            'galones' => ['required', 'array'],
            'galones.*' => ['nullable', 'string', 'max:20'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request): void {
            foreach (TanqueSaldoInicial::COMBUSTIBLES as $combustible) {
                $galones = trim((string) $request->input('galones.'.$combustible, ''));

                // En blanco: ese tanque queda sin saldo inicial.
                if ($galones === '') {
                    TanqueSaldoInicial::query()->where('combustible', $combustible)->delete();

                    continue;
                }

                TanqueSaldoInicial::query()->updateOrCreate(
                    ['combustible' => $combustible],
                    [
                        'fecha_corte' => $request->input('fecha_corte'),
                        'galones' => NumberParser::quantity($galones),
                    ],
                );
            }
        });

        return redirect()->route('control-tanques.index', $request->only('desde', 'hasta'))
            ->with('success', 'Saldo inicial guardado correctamente.');
    }

    public function exportar(Request $request, ControlTanquesExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $libro = $excel->generar($desde, $hasta);

        return DescargaExcel::respuesta($libro, 'control-de-tanques-'.$desde.'-a-'.$hasta.'.xlsx');
    }

    /**
     * Por defecto, el mes en curso hasta hoy.
     *
     * @return array{0: string, 1: string}
     */
    private function rango(Request $request): array
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $desde = Carbon::parse($request->query('desde') ?: now()->startOfMonth());
        $hasta = Carbon::parse($request->query('hasta') ?: now());

        if ($hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        if ($desde->diffInDays($hasta) >= self::MAXIMO_DIAS) {
            $desde = $hasta->copy()->subDays(self::MAXIMO_DIAS - 1);
        }

        return [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];
    }

    private function esFecha(string $valor): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 && strtotime($valor) !== false;
    }
}
