<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Empleado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Certificación laboral del empleado, con o sin salario (el valor sale de su ficha).
 */
class CertificacionLaboralController extends Controller
{
    public function show(Request $request, Empleado $empleado): View|Response
    {
        $opciones = $request->validate([
            'dirigido_a' => ['nullable', 'string', 'max:150'],
            'incluir_salario' => ['nullable', 'boolean'],
        ]);

        $datos = [
            'empleado' => $empleado,
            'dirigidoA' => $opciones['dirigido_a'] ?? 'A QUIEN INTERESE',
            'incluirSalario' => $request->boolean('incluir_salario', true),
            'fecha' => now(),
        ];

        if ($request->boolean('pdf')) {
            return Pdf::loadView('nomina.certificaciones.documento', $datos + ['paraPdf' => true])
                ->setPaper('letter')
                ->download('certificacion-laboral-'.Str::slug($empleado->nombre_completo).'.pdf');
        }

        return view('nomina.certificaciones.show', $datos);
    }
}
