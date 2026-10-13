<?php

namespace App\Http\Controllers;

use App\Concerns\ResuelveRangoFechas;
use App\Models\Lubricant;
use App\Services\KardexExcelService;
use App\Services\KardexInventarioService;
use App\Support\DescargaExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarioLubricanteController extends Controller
{
    use ResuelveRangoFechas;

    public function create(Request $request, KardexInventarioService $kardexService): View
    {
        [$desde, $hasta] = $this->rango($request);
        $productos = Lubricant::query()->orderBy('reference')->pluck('reference')->all();
        $producto = $this->producto($request, $productos);

        return view('inventarios.lubricantes.create', [
            'productos' => $productos,
            'producto' => $producto,
            'kardex' => $producto !== null ? $kardexService->delPeriodo($kardexService->canastilla($producto), $desde, $hasta) : null,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    public function exportar(Request $request, KardexInventarioService $kardexService, KardexExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $producto = $this->producto($request, Lubricant::query()->orderBy('reference')->pluck('reference')->all());
        abort_if($producto === null, 404);

        $kardex = $kardexService->delPeriodo($kardexService->canastilla($producto), $desde, $hasta);

        return DescargaExcel::respuesta(
            $excel->generar('INVENTARIOS '.$producto, 'UNIDADES', $kardex, $desde, $hasta),
            'inventario-canastilla-'.Str::slug($producto).'-'.$desde.'-a-'.$hasta.'.xlsx',
        );
    }

    public function control(Request $request, KardexInventarioService $kardexService): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('inventarios.lubricantes.control', [
            'kardex' => $kardexService->delPeriodo($kardexService->canastillaControl(), $desde, $hasta),
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    public function exportarControl(Request $request, KardexInventarioService $kardexService, KardexExcelService $excel): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $kardex = $kardexService->delPeriodo($kardexService->canastillaControl(), $desde, $hasta);

        return DescargaExcel::respuesta(
            $excel->generar('INVENTARIO CANASTILLA – CONTROL', 'UNIDADES', $kardex, $desde, $hasta, mostrarReferencia: true),
            'inventario-canastilla-control-'.$desde.'-a-'.$hasta.'.xlsx',
        );
    }

    /**
     * @param  list<string>  $productos
     */
    private function producto(Request $request, array $productos): ?string
    {
        return in_array($request->query('producto'), $productos, true)
            ? $request->query('producto')
            : ($productos[0] ?? null);
    }
}
