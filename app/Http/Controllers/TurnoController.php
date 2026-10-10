<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Customer;
use App\Models\FuelPrice;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\TurnoCartera;
use App\Models\TurnoConsignacion;
use App\Models\TurnoDescuento;
use App\Models\TurnoGasolinaEds;
use App\Models\TurnoLubricante;
use App\Models\TurnoQrPago;
use App\Models\TurnoRecaudo;
use App\Models\TurnoRecaudoAdmin;
use App\Models\TurnoSurtidor;
use App\Models\TurnoTransferencia;
use App\Models\TurnoVarios;
use App\Models\TurnoVenta;
use App\Services\CarteraTurnoSyncService;
use App\Services\CuadreTurnoService;
use App\Support\NumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TurnoController extends Controller
{
    public const CONCEPTOS_QR = ['TC', 'QR', 'NEQUI', 'DAVIPLATA', 'TRANSFERENCIA'];

    public function create(Request $request)
    {
        $lubricants = Lubricant::orderBy('reference', 'asc')->get();
        $customers = Customer::orderBy('name', 'asc')->get();
        $bancos = Banco::orderBy('name', 'asc')->get();

        // Si vienen parámetros de búsqueda, cargar el turno
        $turno = null;
        $previousTurno = null;
        $searchFecha = $request->query('fecha');
        $searchNumero = $request->query('numero_turno');
        // Búsqueda independiente de la fecha: se ingresa solo el número de turno
        // y se localiza el registro más reciente que coincida con ese número.
        $searchTurnoSolo = $request->query('turno_busqueda');

        if (! $searchFecha && ! $searchNumero && $searchTurnoSolo) {
            $turno = Turno::with(['ventas', 'surtidores', 'lubricantes', 'consignaciones', 'descuentos', 'cartera', 'qrPagos', 'recaudos', 'transferencias', 'gasolinaEds', 'varios', 'recaudosAdmin'])
                ->where('numero_turno', $searchTurnoSolo)
                ->orderByDesc('fecha')
                ->first();

            if ($turno) {
                $searchFecha = $turno->fecha->toDateString();
                $searchNumero = $turno->numero_turno;
            }
        }

        // Consecutivo global: numero_turno es único en toda la tabla (no se
        // reinicia por fecha), así que el próximo número es el máximo + 1 sin
        // importar la fecha consultada.
        $nextNumber = (int) Turno::max('numero_turno') + 1;

        // Buscador por fecha: lista las planillas registradas ese día para abrirlas.
        $buscarFecha = $request->query('buscar_fecha');
        $turnosDeFecha = $buscarFecha
            ? Turno::query()
                ->whereDate('fecha', $buscarFecha)
                ->orderBy('numero_turno')
                ->get(['id', 'numero_turno', 'fecha', 'nombre_vendedor', 'revisado'])
            : collect();

        if (! $turno && $searchFecha && $searchNumero) {
            // 'fecha' se guarda con hora (cast date -> datetime ISO), por lo que se
            // compara solo la parte de fecha para evitar fallos de coincidencia exacta.
            $turno = Turno::with(['ventas', 'surtidores', 'lubricantes', 'consignaciones', 'descuentos', 'cartera', 'qrPagos', 'recaudos', 'transferencias', 'gasolinaEds', 'varios', 'recaudosAdmin'])
                ->whereDate('fecha', $searchFecha)
                ->where('numero_turno', $searchNumero)
                ->first();
        }

        if (! $turno && ! $searchNumero) {
            // Si es un nuevo turno, cargar el último turno registrado (consecutivo
            // global, sin importar la fecha) para precargar las lecturas iniciales
            // con sus finales.
            $previousTurno = Turno::with('surtidores')
                ->orderByDesc('numero_turno')
                ->first();
        }

        // Si el guardado falló (validación), se vuelve a mostrar lo que se había digitado
        // en lugar de la planilla vacía o de la última versión guardada.
        if ($request->session()->hasOldInput('numero_turno')) {
            $turno = $this->borradorDesdeEnvio($request, $turno);
        }

        return view('planillas.turnos.create', compact('lubricants', 'customers', 'bancos', 'nextNumber', 'turno', 'previousTurno', 'turnosDeFecha', 'buscarFecha', 'searchFecha', 'searchNumero'));
    }

    /**
     * Arma la planilla con los datos enviados en el intento de guardado que no pasó la
     * validación, con las mismas filas y atributos que usan las secciones para mostrar
     * una planilla guardada. No se guarda nada.
     */
    private function borradorDesdeEnvio(Request $request, ?Turno $turno): Turno
    {
        $old = fn (string $seccion): array => array_values(array_filter(
            (array) $request->old($seccion, []),
            'is_array',
        ));

        $numero = (int) $request->old('numero_turno');
        $borrador = $turno?->numero_turno === $numero
            ? $turno
            : (Turno::query()->where('numero_turno', $numero)->first() ?? new Turno([
                'numero_turno' => $numero,
                'precio_corriente' => (float) FuelPrice::activePriceOn('Gasolina', $request->old('fecha')),
                'precio_acpm' => (float) FuelPrice::activePriceOn('ACPM', $request->old('fecha')),
            ]));

        $borrador->fill([
            'fecha' => $request->old('fecha') ?: now()->toDateString(),
            'observaciones' => $request->old('observaciones'),
        ]);

        $filas = fn (string $clase, array $filas): Collection => collect($filas)->map(fn (array $atributos) => new $clase($atributos));

        $borrador->setRelation('ventas', $filas(TurnoVenta::class, array_map(fn (array $r): array => [
            'surtidor' => $r['surtidor'] ?? '', 'combustible' => $r['combustible'] ?? null,
            'galones' => NumberParser::quantity($r['galones'] ?? null), 'valor' => 0,
        ], $old('ventas'))));
        $borrador->setRelation('surtidores', $filas(TurnoSurtidor::class, array_map(fn (array $r): array => [
            'manguera' => $r['manguera'] ?? '', 'combustible' => $r['combustible'] ?? null,
            'lectura_inicial' => NumberParser::quantity($r['lectura_inicial'] ?? null),
            'lectura_final' => NumberParser::quantity($r['lectura_final'] ?? null),
            'galones' => max(0, NumberParser::quantity($r['lectura_final'] ?? null) - NumberParser::quantity($r['lectura_inicial'] ?? null)),
        ], $old('lecturas'))));
        $borrador->setRelation('lubricantes', $filas(TurnoLubricante::class, array_map(fn (array $r): array => [
            'cantidad' => (int) ($r['cantidad'] ?? 0), 'producto' => $r['producto'] ?? null,
            'valor_sin_iva' => NumberParser::money($r['valor_sin_iva'] ?? null), 'iva' => NumberParser::money($r['iva'] ?? null),
            'total' => NumberParser::money($r['total'] ?? null),
        ], $old('urea_lubricantes'))));
        $borrador->setRelation('consignaciones', $filas(TurnoConsignacion::class, array_map(fn (array $r): array => [
            'consignacion_no' => $r['consignacion_no'] ?? null, 'valor' => NumberParser::money($r['consignacion_valor'] ?? null),
        ], $old('consignaciones'))));
        $borrador->setRelation('descuentos', $filas(TurnoDescuento::class, array_map(fn (array $r): array => [
            'descuento_no' => $r['descuento_no'] ?? null, 'valor' => NumberParser::money($r['descuento'] ?? null),
        ], $old('descuentos'))));
        $borrador->setRelation('cartera', $filas(TurnoCartera::class, array_map(fn (array $r): array => [
            'factura_no' => $r['cartera_factura_no'] ?? null, 'cliente_id' => $r['cliente_id'] ?? null,
            'valor' => NumberParser::money($r['cartera_valor'] ?? null),
        ], $old('cartera'))));
        $borrador->setRelation('qrPagos', $filas(TurnoQrPago::class, array_map(fn (array $r): array => [
            'concepto' => $r['concepto'] ?? null, 'valor' => NumberParser::money($r['valor'] ?? null),
        ], $old('qr_pagos'))));
        $borrador->setRelation('recaudos', $filas(TurnoRecaudo::class, array_map(fn (array $r): array => [
            'cliente_id' => $r['cliente_id'] ?? null, 'valor' => NumberParser::money($r['valor'] ?? null),
        ], $old('recaudos'))));
        $borrador->setRelation('transferencias', $filas(TurnoTransferencia::class, array_map(fn (array $r): array => [
            'puntos_redimidos' => NumberParser::money($r['puntos'] ?? null),
        ], $old('transferencias'))));
        $borrador->setRelation('gasolinaEds', $filas(TurnoGasolinaEds::class, array_map(fn (array $r): array => [
            'valor' => NumberParser::money($r['puntos'] ?? null),
        ], $old('gasolina_eds'))));
        $borrador->setRelation('varios', $filas(TurnoVarios::class, array_map(fn (array $r): array => [
            'concepto' => $r['concepto'] ?? null, 'valor' => NumberParser::money($r['valor'] ?? null),
        ], $old('varios'))));

        if (! $request->user()->isIslero()) {
            $borrador->setRelation('recaudosAdmin', $filas(TurnoRecaudoAdmin::class, array_map(fn (array $r): array => [
                'banco' => $r['banco'] ?? null, 'responsable_id' => $r['responsable_id'] ?? null,
                'valor' => NumberParser::money($r['valor'] ?? null),
            ], $old('recaudos_admin'))));
        }

        return $borrador;
    }

    public function store(Request $request, CarteraTurnoSyncService $carteraSync, CuadreTurnoService $cuadre)
    {
        $request->validate([
            'fecha' => ['required', 'date'],
            'numero_turno' => ['required', 'integer', 'min:1'],
            'nombre_vendedor' => ['nullable', 'string', 'max:120'],

            'ventas' => ['nullable', 'array'],
            'lecturas' => ['nullable', 'array'],
            'urea_lubricantes' => ['nullable', 'array'],
            'consignaciones' => ['nullable', 'array'],
            'descuentos' => ['nullable', 'array'],
            'cartera' => ['nullable', 'array'],
            'qr_pagos' => ['nullable', 'array'],
            'recaudos' => ['nullable', 'array'],
            'transferencias' => ['nullable', 'array'],
            'gasolina_eds' => ['nullable', 'array'],
            'varios' => ['nullable', 'array'],
            'recaudos_admin' => ['nullable', 'array'],
            'traslado_sobrante' => ['nullable'],
            'traslado_faltante' => ['nullable'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'qr_pagos.*.concepto' => ['nullable', Rule::in(self::CONCEPTOS_QR)],

            // Cada fila se valida campo por campo: un dato fuera de rango no llega a la base de datos.
            'ventas.*.surtidor' => ['nullable', 'string', 'max:60'],
            'ventas.*.combustible' => ['nullable', Rule::in(['CTE', 'ACPM'])],
            'ventas.*.galones' => ['nullable', 'string', 'max:30'],
            'lecturas.*.manguera' => ['nullable', 'string', 'max:60'],
            'lecturas.*.combustible' => ['nullable', Rule::in(['corriente', 'acpm'])],
            'lecturas.*.lectura_inicial' => ['nullable', 'string', 'max:30'],
            'lecturas.*.lectura_final' => ['nullable', 'string', 'max:30'],
            'urea_lubricantes.*.producto' => ['nullable', 'string', 'max:150'],
            'urea_lubricantes.*.cantidad' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'urea_lubricantes.*.valor_sin_iva' => ['nullable', 'string', 'max:30'],
            'urea_lubricantes.*.iva' => ['nullable', 'string', 'max:30'],
            'urea_lubricantes.*.total' => ['nullable', 'string', 'max:30'],
            'consignaciones.*.consignacion_no' => ['nullable', 'string', 'max:60'],
            'consignaciones.*.consignacion_valor' => ['nullable', 'string', 'max:30'],
            'descuentos.*.descuento_no' => ['nullable', 'string', 'max:60'],
            'descuentos.*.descuento' => ['nullable', 'string', 'max:30'],
            'cartera.*.cartera_factura_no' => ['nullable', 'string', 'max:60'],
            'cartera.*.cliente_id' => ['nullable', 'integer', 'exists:customers,id'],
            'cartera.*.cartera_valor' => ['nullable', 'string', 'max:30'],
            'qr_pagos.*.valor' => ['nullable', 'string', 'max:30'],
            'recaudos.*.cliente_id' => ['nullable', 'integer', 'exists:customers,id'],
            'recaudos.*.valor' => ['nullable', 'string', 'max:30'],
            'transferencias.*.puntos' => ['nullable', 'string', 'max:30'],
            'gasolina_eds.*.puntos' => ['nullable', 'string', 'max:30'],
            'varios.*.concepto' => ['nullable', 'string', 'max:150'],
            'varios.*.valor' => ['nullable', 'string', 'max:30'],
            'recaudos_admin.*.banco' => ['nullable', 'string', 'max:60'],
            'recaudos_admin.*.responsable_id' => ['nullable', 'integer', 'exists:customers,id'],
            'recaudos_admin.*.valor' => ['nullable', 'string', 'max:30'],
        ]);

        // numero_turno es un consecutivo único en toda la tabla, así que basta
        // con buscarlo por ese campo para detectar si ya existe y actualizarlo
        // en vez de intentar crear uno duplicado (violaría el unique numero_turno).
        $existente = Turno::query()->where('numero_turno', $request->input('numero_turno'))->first();

        // Una planilla revisada no se modifica ni se alimenta (ni siquiera el
        // administrador): primero debe volverla a PENDIENTE DE REVISIÓN.
        if ($existente && $existente->revisado) {
            abort(403, 'Esta planilla está REVISADA y no se puede modificar. El administrador debe volverla a Pendiente de revisión.');
        }

        if ($existente && ! $existente->editablePor($request->user())) {
            abort(403, 'Esta planilla la registró otro usuario; solo él, el jefe de patios o el administrador la pueden modificar.');
        }

        // Cambiar la fecha de una planilla ya guardada la movería de día en todos los informes.
        if ($existente && ! $request->user()->isAdministrador()
            && $existente->fecha->format('Y-m-d') !== date('Y-m-d', strtotime((string) $request->input('fecha')))) {
            throw ValidationException::withMessages(['fecha' => 'Solo el administrador puede cambiar la fecha de una planilla ya guardada.']);
        }

        $this->validarClientes($request);

        DB::transaction(function () use ($request, $carteraSync, $cuadre) {
            $turno = Turno::query()
                ->where('numero_turno', $request->input('numero_turno'))
                ->lockForUpdate()
                ->first();

            // Solo el administrador puede reversar un traslado ya guardado; para
            // los demás roles se conserva el traslado registrado.
            $trasladoSobrante = $this->trasladoValue($request->input('traslado_sobrante'));
            $trasladoFaltante = $this->trasladoValue($request->input('traslado_faltante'));

            // Reversar = enviar el traslado en cero cuando ya había uno guardado. Ajustarlo
            // (trasladar una diferencia nueva) sí lo puede hacer quien modifica la planilla.
            $reversa = $trasladoSobrante === 0.0 && $trasladoFaltante === 0.0;

            if ($turno && $reversa && ! $request->user()->isAdministrador()
                && ((float) $turno->traslado_sobrante !== 0.0 || (float) $turno->traslado_faltante !== 0.0)) {
                $trasladoSobrante = (float) $turno->traslado_sobrante;
                $trasladoFaltante = (float) $turno->traslado_faltante;
            }

            $atributos = [
                'fecha' => $request->input('fecha'),
                'numero_turno' => $request->input('numero_turno'),
                // El vendedor es quien registró la planilla; si otro la corrige, no se reemplaza.
                'nombre_vendedor' => $turno?->nombre_vendedor ?: $request->user()->name,
                'observaciones' => $request->input('observaciones'),
                // Sin fallback a config(): el precio debe salir de un registro vigente
                // en Precios de Combustible; si no existe, queda en 0.
                'precio_corriente' => (float) FuelPrice::activePriceOn('Gasolina', $request->input('fecha')),
                'precio_acpm' => (float) FuelPrice::activePriceOn('ACPM', $request->input('fecha')),
                // Estado del traslado de sobrante/faltante (botón TRASLADAR en
                // sobrantes.blade.php): debe persistir para que el resumen no
                // vuelva a mostrar el faltante/sobrante original al recargar.
                'traslado_sobrante' => $trasladoSobrante,
                'traslado_faltante' => $trasladoFaltante,
            ];

            if ($turno) {
                $turno->update($atributos);
                $turno->ventas()->delete();
                $turno->surtidores()->delete();
                $turno->lubricantes()->delete();
                $turno->consignaciones()->delete();
                $turno->descuentos()->delete();
                $turno->cartera()->delete();
                $turno->qrPagos()->delete();
                $turno->recaudos()->delete();
                $turno->transferencias()->delete();
                $turno->gasolinaEds()->delete();
                $turno->varios()->delete();
                // RECAUDOS POR ADMINISTRACIÓN está bloqueado para el rol islero; sus
                // filas no se tocan si quién guarda no tiene permiso de administración.
                if (! $request->user()->isIslero()) {
                    $turno->recaudosAdmin()->delete();
                }
            } else {
                $turno = Turno::create($atributos + ['user_id' => $request->user()->id]);
            }

            $this->saveVentas($turno, $request->input('ventas', []));
            $this->saveLecturas($turno, $request->input('lecturas', []));
            $this->saveUreaLubricantes($turno, $request->input('urea_lubricantes', []));
            $this->saveConsignaciones($turno, $request->input('consignaciones', []));
            $this->saveDescuentos($turno, $request->input('descuentos', []));
            $this->saveCartera($turno, $request->input('cartera', []));
            $this->saveQrPagos($turno, $request->input('qr_pagos', []));
            $this->saveRecaudos($turno, $request->input('recaudos', []));
            $this->saveTransferencias($turno, $request->input('transferencias', []));
            $this->saveGasolinaEds($turno, $request->input('gasolina_eds', []));
            $this->saveVarios($turno, $request->input('varios', []));
            if (! $request->user()->isIslero()) {
                $this->saveRecaudosAdmin($turno, $request->input('recaudos_admin', []));
            }
            $this->saveVentasTotales($turno);
            $this->saveVentaLecturasTotales($turno);

            // Sobrante/faltante sin trasladar, calculado aquí y no en el navegador:
            // impide marcar la planilla como revisada mientras no sea cero.
            $turno->update(['sobrante_pendiente' => $cuadre->pendiente($turno->fresh())]);

            // Al final: la cartera lee créditos y recaudos ya guardados del turno.
            $carteraSync->sincronizar($turno);
        });

        return redirect()->route('turnos.create', [
            'fecha' => $request->input('fecha'),
            'numero_turno' => $request->input('numero_turno'),
        ])
            ->with('success', 'Turno guardado correctamente.');
    }

    public function pendientes(Request $request)
    {
        $turnos = Turno::query()
            ->where('revisado', false)
            ->orderByDesc('fecha')
            ->orderByDesc('numero_turno')
            ->paginate(20);

        return view('planillas.turnos.pendientes', compact('turnos'));
    }

    public function revisar(Request $request, Turno $turno)
    {
        // No se revisa una planilla con SOBRANTE o FALTANTE sin trasladar.
        if ($turno->sobrante_pendiente === null) {
            return back()->withErrors(['revisar' => 'Turno #'.$turno->numero_turno.': abra la planilla y guárdela antes de marcarla como revisada, para verificar el sobrante o faltante.']);
        }

        if (abs((float) $turno->sobrante_pendiente) >= 1) {
            $tipo = (float) $turno->sobrante_pendiente < 0 ? 'FALTANTE' : 'SOBRANTE';

            return back()->withErrors(['revisar' => 'Turno #'.$turno->numero_turno.': tiene un '.$tipo.' de $'.number_format(abs((float) $turno->sobrante_pendiente), 0, ',', '.').' sin trasladar. Use TRASLADAR y guarde la planilla antes de marcarla como revisada.']);
        }

        $turno->update([
            'revisado' => true,
            'revisado_por' => $request->user()->name,
            'revisado_at' => now(),
        ]);

        return back()->with('success', 'Turno #'.$turno->numero_turno.' del '.$turno->fecha->format('Y-m-d').' marcado como revisado.');
    }

    /**
     * El administrador devuelve una planilla revisada a PENDIENTE DE REVISIÓN
     * para hacerle correcciones o adiciones.
     */
    public function reabrir(Request $request, Turno $turno)
    {
        $turno->update([
            'revisado' => false,
            'revisado_por' => null,
            'revisado_at' => null,
        ]);

        return redirect()->route('turnos.create', ['turno_busqueda' => $turno->numero_turno])
            ->with('success', 'Turno #'.$turno->numero_turno.' devuelto a PENDIENTE DE REVISIÓN. Ya se puede corregir.');
    }

    /**
     * CARTERA - CRÉDITO DIRECTO, RECAUDOS Y ANTICIPOS POR ISLAS y RECAUDOS POR
     * ADMINISTRACIÓN no admiten un valor sin CLIENTE, y en CRÉDITO DIRECTO el
     * cliente y el valor exigen el No. de FACTURA. La planilla ya lo impide en
     * pantalla; esto es el respaldo del lado del servidor.
     */
    private function validarClientes(Request $request): void
    {
        $secciones = [
            'cartera' => ['valor' => 'cartera_valor', 'cliente' => 'cliente_id', 'titulo' => 'CARTERA - CRÉDITO DIRECTO'],
            'recaudos' => ['valor' => 'valor', 'cliente' => 'cliente_id', 'titulo' => 'RECAUDOS Y ANTICIPOS POR ISLAS'],
        ];

        // El islero no alimenta RECAUDOS POR ADMINISTRACIÓN (sus filas no se guardan).
        if (! $request->user()->isIslero()) {
            $secciones['recaudos_admin'] = ['valor' => 'valor', 'cliente' => 'responsable_id', 'titulo' => 'RECAUDOS POR ADMINISTRACIÓN'];
        }

        $errores = [];

        foreach ((array) $request->input('cartera', []) as $row) {
            if (! is_array($row) || trim((string) ($row['cartera_factura_no'] ?? '')) !== '') {
                continue;
            }

            if (! empty($row['cliente_id']) || NumberParser::money($row['cartera_valor'] ?? null) != 0) {
                $errores['cartera'] = 'En CARTERA - CRÉDITO DIRECTO hay filas sin No. de FACTURA. Digite la factura antes del cliente y el valor.';
            }
        }

        foreach ((array) $request->input('qr_pagos', []) as $row) {
            if (is_array($row) && ! $this->filaVacia($row, ['valor']) && trim((string) ($row['concepto'] ?? '')) === '') {
                $errores['qr_pagos'] = 'En PAGOS ELECTRÓNICOS Y TRANSFERENCIAS hay valores sin CONCEPTO. Seleccione el concepto de cada fila.';
            }
        }

        foreach ($secciones as $campo => $seccion) {
            if (isset($errores[$campo])) {
                continue;
            }

            foreach ((array) $request->input($campo, []) as $row) {
                if (! is_array($row)) {
                    continue;
                }

                if (NumberParser::money($row[$seccion['valor']] ?? null) != 0 && empty($row[$seccion['cliente']])) {
                    $errores[$campo] = 'En '.$seccion['titulo'].' hay valores sin CLIENTE. Seleccione el cliente de cada fila.';
                }
            }
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * El traslado lo escribe el navegador como número plano (ej. "-98000" o
     * "-98000.00"). Leerlo como dinero borraba el punto decimal y multiplicaba
     * el valor por 100 en cada guardado.
     */
    private function trasladoValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return is_numeric($value) ? round((float) $value) : NumberParser::money($value);
    }

    private function saveVentas(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(function (array $row) use ($turno): float {
            $precio = ($row['combustible'] ?? null) === 'ACPM'
                ? $turno->precio_acpm
                : $turno->precio_corriente;

            return NumberParser::quantity($row['galones'] ?? null) * $precio;
        });

        foreach ($rows as $row) {
            $galones = NumberParser::quantity($row['galones'] ?? null);
            $surtidor = trim($row['surtidor'] ?? '');
            $combustible = $row['combustible'] ?? null;

            if (! $surtidor && ! $combustible) {
                continue;
            }

            $precio = $combustible === 'ACPM'
                ? $turno->precio_acpm
                : $turno->precio_corriente;

            $turno->ventas()->create([
                'surtidor' => $surtidor,
                'combustible' => $combustible,
                'galones' => $galones,
                'valor' => $galones * $precio,
                'total' => $total,
            ]);
        }
    }

    /**
     * Recompute and persist the "venta en tirillas de cortes" aggregates and
     * the grand total from the rows just saved, para que informes puedan
     * consultarlos directamente desde turnos sin recalcular turno_ventas.
     */
    private function saveVentasTotales(Turno $turno): void
    {
        $ventas = $turno->ventas()->get();

        $galonesCte = (float) $ventas->where('combustible', 'CTE')->sum('galones');
        $galonesAcpm = (float) $ventas->where('combustible', 'ACPM')->sum('galones');

        $turno->update([
            'tirillas_galones_corriente' => $galonesCte,
            'tirillas_galones_acpm' => $galonesAcpm,
            'tirillas_valor_corriente' => $galonesCte * $turno->precio_corriente,
            'tirillas_valor_acpm' => $galonesAcpm * $turno->precio_acpm,
            'total_ventas' => (float) $ventas->sum('valor'),
        ]);
    }

    private function saveLecturas(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(function (array $row): float {
            $inicial = NumberParser::quantity($row['lectura_inicial'] ?? null);
            $final = NumberParser::quantity($row['lectura_final'] ?? null);

            return max(0, $final - $inicial);
        });

        foreach ($rows as $row) {
            $inicial = NumberParser::quantity($row['lectura_inicial'] ?? null);
            $final = NumberParser::quantity($row['lectura_final'] ?? null);
            $galones = max(0, $final - $inicial);
            $manguera = trim($row['manguera'] ?? '');
            $combustible = $row['combustible'] ?? null;

            if (! $manguera) {
                continue;
            }

            $turno->surtidores()->create([
                'manguera' => $manguera,
                'combustible' => $combustible,
                'lectura_inicial' => $inicial,
                'lectura_final' => $final,
                'galones' => $galones,
                'total' => $total,
            ]);
        }
    }

    /**
     * Recompute and persist the "venta según lecturas" aggregates (galones y
     * valor por CORRIENTE/ACPM y total) desde turno_surtidores, para que
     * informes puedan consultarlos directamente desde el turno.
     */
    private function saveVentaLecturasTotales(Turno $turno): void
    {
        $surtidores = $turno->surtidores()->get();

        $galonesCorriente = (float) $surtidores->where('combustible', 'corriente')->sum('galones');
        $galonesAcpm = (float) $surtidores->where('combustible', 'acpm')->sum('galones');
        $valorCorriente = $galonesCorriente * $turno->precio_corriente;
        $valorAcpm = $galonesAcpm * $turno->precio_acpm;

        $turno->update([
            'lecturas_galones_corriente' => $galonesCorriente,
            'lecturas_galones_acpm' => $galonesAcpm,
            'lecturas_valor_corriente' => $valorCorriente,
            'lecturas_valor_acpm' => $valorAcpm,
            'total_venta_lecturas' => $valorCorriente + $valorAcpm,
        ]);
    }

    /**
     * Una fila de la planilla solo se descarta cuando todos sus campos están en blanco.
     * Un 0 digitado es un dato y se guarda (antes las filas en 0 se perdían al guardar).
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $campos
     */
    private function filaVacia(array $row, array $campos): bool
    {
        foreach ($campos as $campo) {
            if (trim((string) ($row[$campo] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function saveUreaLubricantes(Turno $turno, array $rows): void
    {
        foreach ($rows as $row) {
            $cantidad = (int) ($row['cantidad'] ?? 0);
            $producto = $row['producto'] ?? null;
            $valorSinIva = NumberParser::money($row['valor_sin_iva'] ?? null);
            $iva = NumberParser::money($row['iva'] ?? null);
            $total = NumberParser::money($row['total'] ?? ($cantidad * ($valorSinIva + $iva)));

            // Sin producto no hay venta de canastilla; con producto se conserva aunque la cantidad sea 0.
            if (! $producto) {
                continue;
            }

            $turno->lubricantes()->create([
                'cantidad' => $cantidad,
                'producto' => $producto,
                'valor_sin_iva' => $valorSinIva,
                'iva' => $iva,
                'total' => $total,
            ]);
        }
    }

    private function saveConsignaciones(Turno $turno, array $rows): void
    {
        $values = collect($rows)
            ->reject(fn (array $row): bool => $this->filaVacia($row, ['consignacion_no', 'consignacion_valor']))
            ->map(fn (array $row): array => [
                'consignacion_no' => $row['consignacion_no'] ?? null,
                'valor' => NumberParser::money($row['consignacion_valor'] ?? null),
            ])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->consignaciones()->create([
                'consignacion_no' => $row['consignacion_no'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    private function saveDescuentos(Turno $turno, array $rows): void
    {
        $values = collect($rows)
            ->reject(fn (array $row): bool => $this->filaVacia($row, ['descuento_no', 'descuento']))
            ->map(fn (array $row): array => [
                'descuento_no' => $row['descuento_no'] ?? null,
                'valor' => NumberParser::money($row['descuento'] ?? null),
            ])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->descuentos()->create([
                'descuento_no' => $row['descuento_no'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    private function saveCartera(Turno $turno, array $rows): void
    {
        $values = collect($rows)
            ->reject(fn (array $row): bool => $this->filaVacia($row, ['cartera_factura_no', 'cliente_id', 'cartera_valor']))
            ->map(fn (array $row): array => [
                'factura_no' => $row['cartera_factura_no'] ?? null,
                'cliente_id' => ($row['cliente_id'] ?? null) ?: null,
                'valor' => NumberParser::money($row['cartera_valor'] ?? null),
            ])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->cartera()->create([
                'factura_no' => $row['factura_no'],
                'cliente_id' => $row['cliente_id'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    private function saveQrPagos(Turno $turno, array $rows): void
    {
        // El concepto trae un valor por defecto en las filas nuevas: la fila cuenta si tiene valor.
        $values = collect($rows)
            ->reject(fn (array $row): bool => $this->filaVacia($row, ['valor']))
            ->map(fn (array $row): array => [
                'concepto' => $row['concepto'] ?? null,
                'valor' => NumberParser::money($row['valor'] ?? null),
            ])->values();
        $total = $values->sum('valor');

        foreach ($values as $row) {
            $turno->qrPagos()->create([
                'concepto' => $row['concepto'],
                'valor' => $row['valor'],
                'total' => $total,
            ]);
        }
    }

    private function saveRecaudos(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['valor'] ?? null));

        foreach ($rows as $row) {
            $valor = NumberParser::money($row['valor'] ?? null);
            $clienteId = ($row['cliente_id'] ?? null) ?: null;

            if ($this->filaVacia($row, ['cliente_id', 'valor'])) {
                continue;
            }

            $turno->recaudos()->create([
                'cliente_id' => $clienteId,
                'valor' => $valor,
                'total' => $total,
            ]);
        }
    }

    private function saveTransferencias(Turno $turno, array $rows): void
    {
        $totalPuntos = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['puntos'] ?? null));

        foreach ($rows as $row) {
            $puntos = NumberParser::money($row['puntos'] ?? null);

            if ($this->filaVacia($row, ['puntos'])) {
                continue;
            }

            $turno->transferencias()->create([
                'puntos_redimidos' => $puntos,
                'total_puntos' => $totalPuntos,
            ]);
        }
    }

    private function saveGasolinaEds(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['puntos'] ?? null));

        foreach ($rows as $row) {
            $valor = NumberParser::money($row['puntos'] ?? null);

            if ($this->filaVacia($row, ['puntos'])) {
                continue;
            }

            $turno->gasolinaEds()->create([
                'valor' => $valor,
                'total' => $total,
            ]);
        }
    }

    private function saveVarios(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['valor'] ?? null));

        foreach ($rows as $row) {
            $concepto = trim((string) ($row['concepto'] ?? ''));
            $valor = NumberParser::money($row['valor'] ?? null);

            if ($this->filaVacia($row, ['concepto', 'valor'])) {
                continue;
            }

            $turno->varios()->create([
                'concepto' => $concepto,
                'valor' => $valor,
                'total' => $total,
            ]);
        }
    }

    private function saveRecaudosAdmin(Turno $turno, array $rows): void
    {
        $total = collect($rows)->sum(fn (array $row): float => NumberParser::money($row['valor'] ?? null));

        foreach ($rows as $row) {
            $banco = $row['banco'] ?? null;
            $responsableId = ($row['responsable_id'] ?? null) ?: null;
            $valor = NumberParser::money($row['valor'] ?? null);

            if ($this->filaVacia($row, ['banco', 'responsable_id', 'valor'])) {
                continue;
            }

            $turno->recaudosAdmin()->create([
                'banco' => $banco,
                'responsable_id' => $responsableId,
                'valor' => $valor,
                'total' => $total,
            ]);
        }
    }
}
