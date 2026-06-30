<?php

namespace App\Http\Controllers;

use App\Models\AnticipoBimestral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnticipoBimestralController extends Controller
{
    public function create()
    {
        return view('anticipos_bimestrales.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'bimestre' => ['required', 'integer', 'min:1', 'max:6'],
            'periodo' => ['required', 'string', 'max:60'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.mes' => ['nullable', 'string', 'max:30'],
            'detalles.*.galones' => ['nullable', 'string', 'max:40'],
            'detalles.*.valor_intermediacion' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request) {
            $bimestre = (int) $request->input('bimestre');
            $periodo = trim((string) $request->input('periodo'));

            foreach ($request->input('detalles', []) as $row) {
                $mes = trim((string) ($row['mes'] ?? ''));
                $galones = $this->parseDecimal($row['galones'] ?? null);
                $valorIntermediacion = $this->parseDecimal($row['valor_intermediacion'] ?? null);

                if ($mes === '' && $galones === 0.0 && $valorIntermediacion === 0.0) {
                    continue;
                }

                $pesos = round($galones * $valorIntermediacion, 0);

                AnticipoBimestral::create([
                    'bimestre' => $bimestre,
                    'periodo' => $periodo,
                    'mes' => $mes,
                    'galones' => $galones,
                    'valor_intermediacion' => $valorIntermediacion,
                    'pesos' => $pesos,
                ]);
            }
        });

        return redirect()->route('anticipo-bimestral.create')
            ->with('success', 'Anticipo bimestral guardado correctamente.');
    }

    private function parseDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = str_replace(['.', ' '], ['', ''], (string) $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
