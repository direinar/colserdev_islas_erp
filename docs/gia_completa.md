Aquí está la guía completa, paso a paso:

Estrategia general
El formulario es una unidad lógica: un turno con múltiples secciones. La arquitectura correcta es un único <form> en la plantilla principal con un solo botón de guardar, y los partials simplemente viven dentro de ese form. Las tablas de detalle (medios de pago, lubricantes, etc.) se guardan en tablas hijas con turno_id como FK.

Paso 1 — Migración de la tabla raíz
bashphp artisan make:migration create_turnos_table
php// database/migrations/xxxx_create_turnos_table.php
Schema::create('turnos', function (Blueprint $table) {
    $table->id();
    $table->date('fecha');
    $table->unsignedTinyInteger('numero_turno'); // 1, 2 o 3
    $table->string('nombre_vendedor')->nullable();
    $table->string('revisado')->nullable();
    $table->timestamps();
});

Paso 2 — Migraciones de las tablas hijas
Cada tabla hija tiene turno_id como FK. Ejecuta make:migration para cada una:
bashphp artisan make:migration create_turno_ventas_table
php artisan make:migration create_turno_surtidores_table
php artisan make:migration create_turno_lubricantes_table
php artisan make:migration create_turno_medios_pago_table
php artisan make:migration create_turno_qr_pagos_table
php artisan make:migration create_turno_recaudos_table
php artisan make:migration create_turno_transferencias_table
php artisan make:migration create_turno_gasolina_eds_table
php artisan make:migration create_turno_varios_table
php artisan make:migration create_turno_recaudos_admin_table
turno_ventas — ventas por surtidor según IAPROPIADA:
phpSchema::create('turno_ventas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->string('surtidor');        // "SURTIDOR 1 CTE"
    $table->string('combustible');     // 'corriente' | 'acpm'
    $table->decimal('galones', 10, 3)->default(0);
    $table->decimal('valor', 15, 2)->default(0);
    $table->timestamps();
});
turno_surtidores — lecturas electrónicas por manguera:
phpSchema::create('turno_surtidores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->string('manguera');        // "PLUS 01", "ACPM 03"
    $table->string('combustible');     // 'corriente' | 'acpm'
    $table->decimal('lectura_inicial', 12, 3)->default(0);
    $table->decimal('lectura_final', 12, 3)->default(0);
    $table->decimal('galones', 10, 3)->default(0); // calculado
    $table->timestamps();
});
turno_lubricantes — urea y lubricantes:
phpSchema::create('turno_lubricantes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->unsignedInteger('cantidad')->default(0);
    $table->string('producto');        // referencia del producto
    $table->decimal('valor_sin_iva', 15, 2)->default(0);
    $table->decimal('iva', 15, 2)->default(0);
    $table->decimal('total', 15, 2)->default(0);
    $table->timestamps();
});
turno_medios_pago — consignaciones + cartera:
phpSchema::create('turno_medios_pago', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->string('consignacion_no')->nullable();
    $table->decimal('consignacion_valor', 15, 2)->default(0);
    $table->decimal('descuento', 15, 2)->default(0);
    $table->string('cartera_factura_no')->nullable();
    $table->foreignId('cliente_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->decimal('cartera_valor', 15, 2)->default(0);
    $table->timestamps();
});
turno_qr_pagos — TC, QR, Nequi, Daviplata:
phpSchema::create('turno_qr_pagos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->string('concepto');        // "Datáfono 1"
    $table->decimal('valor', 15, 2)->default(0);
    $table->timestamps();
});
turno_recaudos — recaudos/anticipos por islas:
phpSchema::create('turno_recaudos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->foreignId('cliente_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->decimal('valor', 15, 2)->default(0);
    $table->timestamps();
});
turno_transferencias — transferencias Bancolombia:
phpSchema::create('turno_transferencias', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->decimal('valor', 15, 2)->default(0);
    $table->decimal('puntos_redimidos', 15, 2)->default(0);
    $table->timestamps();
});
turno_gasolina_eds:
phpSchema::create('turno_gasolina_eds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->decimal('valor', 15, 2)->default(0);
    $table->timestamps();
});
turno_varios:
phpSchema::create('turno_varios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->string('concepto');
    $table->decimal('valor', 15, 2)->default(0);
    $table->timestamps();
});
turno_recaudos_admin:
phpSchema::create('turno_recaudos_admin', function (Blueprint $table) {
    $table->id();
    $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
    $table->string('banco')->nullable();
    $table->foreignId('responsable_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->decimal('valor', 15, 2)->default(0);
    $table->timestamps();
});
bashphp artisan migrate

Paso 3 — Modelos
bashphp artisan make:model Turno
php artisan make:model TurnoVenta
php artisan make:model TurnoSurtidor
php artisan make:model TurnoLubricante
php artisan make:model TurnoMedioPago
php artisan make:model TurnoQrPago
php artisan make:model TurnoRecaudo
php artisan make:model TurnoTransferencia
php artisan make:model TurnoGasolinaEds
php artisan make:model TurnoVarios
php artisan make:model TurnoRecaudoAdmin
Turno.php — modelo raíz con todas las relaciones:
phpclass Turno extends Model
{
    protected $fillable = ['fecha', 'numero_turno', 'nombre_vendedor', 'revisado'];

    protected $casts = ['fecha' => 'date'];

    public function ventas()        { return $this->hasMany(TurnoVenta::class); }
    public function surtidores()    { return $this->hasMany(TurnoSurtidor::class); }
    public function lubricantes()   { return $this->hasMany(TurnoLubricante::class); }
    public function mediosPago()    { return $this->hasMany(TurnoMedioPago::class); }
    public function qrPagos()       { return $this->hasMany(TurnoQrPago::class); }
    public function recaudos()      { return $this->hasMany(TurnoRecaudo::class); }
    public function transferencias(){ return $this->hasMany(TurnoTransferencia::class); }
    public function gasolinaEds()   { return $this->hasMany(TurnoGasolinaEds::class); }
    public function varios()        { return $this->hasMany(TurnoVarios::class); }
    public function recaudosAdmin() { return $this->hasMany(TurnoRecaudoAdmin::class); }
}
Cada modelo hijo es simple — ejemplo para TurnoMedioPago:
phpclass TurnoMedioPago extends Model
{
    protected $table = 'turno_medios_pago';
    protected $fillable = [
        'turno_id', 'consignacion_no', 'consignacion_valor',
        'descuento', 'cartera_factura_no', 'cliente_id', 'cartera_valor'
    ];
}
El patrón es el mismo para todos: $fillable con los campos de su migración.

Paso 4 — Controlador
bashphp artisan make:controller TurnoController
php// app/Http/Controllers/TurnoController.php
class TurnoController extends Controller
{
    public function create()
    {
        return view('planillas.turnos.create', [
            'lubricants' => Lubricant::all(),
            'customers'  => Customer::all(),
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validar campos del encabezado
        $request->validate([
            'fecha'         => 'required|date',
            'numero_turno'  => 'required|integer|min:1|max:3',
        ]);

        DB::transaction(function () use ($request) {

            // 2. Crear el turno raíz
            $turno = Turno::create([
                'fecha'           => $request->fecha,
                'numero_turno'    => $request->numero_turno,
                'nombre_vendedor' => $request->nombre_vendedor,
                'revisado'        => $request->revisado,
            ]);

            // 3. Guardar cada sección como colección de hijos
            $this->saveVentas($turno, $request);
            $this->saveSurtidores($turno, $request);
            $this->saveLubricantes($turno, $request);
            $this->saveMediosPago($turno, $request);
            $this->saveQrPagos($turno, $request);
            $this->saveRecaudos($turno, $request);
            $this->saveTransferencias($turno, $request);
            $this->saveGasolinaEds($turno, $request);
            $this->saveVarios($turno, $request);
            $this->saveRecaudosAdmin($turno, $request);
        });

        return redirect()->route('turnos.index')
                         ->with('success', 'Turno guardado correctamente.');
    }

    // ── Métodos privados por sección ──────────────────────────────────────

    private function parseNumber(?string $value): float
    {
        // Convierte "1.234.567,89" → 1234567.89
        $clean = str_replace('.', '', $value ?? '0');
        $clean = str_replace(',', '.', $clean);
        return (float) $clean ?: 0.0;
    }

    private function saveVentas(Turno $turno, Request $request): void
    {
        foreach ($request->input('ventas', []) as $row) {
            if (empty($row['surtidor'])) continue;
            $turno->ventas()->create([
                'surtidor'    => $row['surtidor'],
                'combustible' => $row['combustible'],
                'galones'     => $this->parseNumber($row['galones'] ?? null),
                'valor'       => $this->parseNumber($row['valor']   ?? null),
            ]);
        }
    }

    private function saveSurtidores(Turno $turno, Request $request): void
    {
        foreach ($request->input('surtidores', []) as $row) {
            if (empty($row['manguera'])) continue;
            $turno->surtidores()->create([
                'manguera'        => $row['manguera'],
                'combustible'     => $row['combustible'],
                'lectura_inicial' => $this->parseNumber($row['lectura_inicial'] ?? null),
                'lectura_final'   => $this->parseNumber($row['lectura_final']   ?? null),
                'galones'         => $this->parseNumber($row['galones']         ?? null),
            ]);
        }
    }

    private function saveLubricantes(Turno $turno, Request $request): void
    {
        foreach ($request->input('urea_lubricantes', []) as $row) {
            if (empty($row['producto'])) continue;
            $turno->lubricantes()->create([
                'cantidad'      => (int) ($row['cantidad']    ?? 0),
                'producto'      => $row['producto'],
                'valor_sin_iva' => $this->parseNumber($row['valor_sin_iva'] ?? null),
                'iva'           => $this->parseNumber($row['iva']           ?? null),
                'total'         => $this->parseNumber($row['total']         ?? null),
            ]);
        }
    }

    private function saveMediosPago(Turno $turno, Request $request): void
    {
        foreach ($request->input('medios_pago', []) as $row) {
            $turno->mediosPago()->create([
                'consignacion_no'    => $row['consignacion_no']    ?? null,
                'consignacion_valor' => $this->parseNumber($row['consignacion_valor'] ?? null),
                'descuento'          => $this->parseNumber($row['descuento']          ?? null),
                'cartera_factura_no' => $row['cartera_factura_no'] ?? null,
                'cliente_id'         => $row['cliente_id']         ?: null,
                'cartera_valor'      => $this->parseNumber($row['cartera_valor']      ?? null),
            ]);
        }
    }

    private function saveQrPagos(Turno $turno, Request $request): void
    {
        foreach ($request->input('qr_pagos', []) as $row) {
            $concepto = array_values(array_filter(
                $row, fn($v, $k) => $k !== 'valor', ARRAY_FILTER_USE_BOTH
            ));
            $turno->qrPagos()->create([
                'concepto' => $concepto[0] ?? 'Datáfono',
                'valor'    => $this->parseNumber($row['valor'] ?? null),
            ]);
        }
    }

    private function saveRecaudos(Turno $turno, Request $request): void
    {
        foreach ($request->input('recaudos', []) as $row) {
            $turno->recaudos()->create([
                'cliente_id' => $row['cliente_id'] ?: null,
                'valor'      => $this->parseNumber($row['valor'] ?? null),
            ]);
        }
    }

    private function saveTransferencias(Turno $turno, Request $request): void
    {
        foreach ($request->input('transferencias', []) as $row) {
            $turno->transferencias()->create([
                'valor'            => $this->parseNumber($row['valor']  ?? null),
                'puntos_redimidos' => $this->parseNumber($row['puntos'] ?? null),
            ]);
        }
    }

    private function saveGasolinaEds(Turno $turno, Request $request): void
    {
        foreach ($request->input('gasolina_eds', []) as $row) {
            $turno->gasolinaEds()->create([
                'valor' => $this->parseNumber($row['puntos'] ?? null),
            ]);
        }
    }

    private function saveVarios(Turno $turno, Request $request): void
    {
        foreach ($request->input('varios', []) as $row) {
            if (empty($row['concepto'])) continue;
            $turno->varios()->create([
                'concepto' => $row['concepto'],
                'valor'    => $this->parseNumber($row['valor'] ?? null),
            ]);
        }
    }

    private function saveRecaudosAdmin(Turno $turno, Request $request): void
    {
        foreach ($request->input('recaudos_admin', []) as $row) {
            $turno->recaudosAdmin()->create([
                'banco'          => $row['banco']          ?? null,
                'responsable_id' => $row['responsable_id'] ?: null,
                'valor'          => $this->parseNumber($row['valor'] ?? null),
            ]);
        }
    }
}

Paso 5 — Rutas
php// routes/web.php
use App\Http\Controllers\TurnoController;

Route::get('/turnos/crear',   [TurnoController::class, 'create'])->name('turnos.create');
Route::post('/turnos',        [TurnoController::class, 'store']) ->name('turnos.store');
Route::get('/turnos',         [TurnoController::class, 'index']) ->name('turnos.index');

Paso 6 — El <form> en la plantilla principal
Esta es la clave de todo. El <form> envuelve todo el contenido en create.blade.php, y el botón de guardar va una sola vez al final:
blade{{-- resources/views/planillas/turnos/create.blade.php --}}
@extends('layouts.app')

@section('content')

<form action="{{ route('turnos.store') }}" method="POST" id="form-turno">
    @csrf

    {{-- HEADER con fecha y turno --}}
    <div class="bg-dark text-white p-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h4 class="mb-0">PLANTILLA DE TURNOS</h4>
            </div>
            <div class="col-md-6 text-end">
                FECHA:
                <input type="date" name="fecha"
                       class="form-control form-control-sm d-inline-block w-auto"
                       value="{{ date('Y-m-d') }}" required>
                TURNO:
                <input type="number" name="numero_turno"
                       class="form-control form-control-sm d-inline-block"
                       style="width:80px" min="1" max="3" required>
            </div>
        </div>
    </div>

    {{-- CONTENIDO (igual a tu plantilla actual) --}}
    <div class="row">
        <div class="col-lg-4">
            @include('planillas.turnos.partials.ventas')
            @include('planillas.turnos.partials.surtidores')
            @include('planillas.turnos.partials.lubricantes')
            @include('planillas.turnos.partials.resumen')
            @include('planillas.turnos.partials.sobrantes')
        </div>
        <div class="col-lg-8">
            @include('planillas.turnos.partials.medios_pago')
            <div class="row mt-3">
                <div class="col-md-4">@include('planillas.turnos.partials.qr')</div>
                <div class="col-md-4">@include('planillas.turnos.partials.recaudos')</div>
                <div class="col-md-4">@include('planillas.turnos.partials.transferencias')</div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">@include('planillas.turnos.partials.gasolina_eds')</div>
                <div class="col-md-4">@include('planillas.turnos.partials.varios')</div>
            </div>
            <div class="row mt-3">
                <div class="col-lg-6">@include('planillas.turnos.partials.resumen_recibido_turno')</div>
                <div class="col-lg-6">@include('planillas.turnos.partials.recaudos_admin')</div>
            </div>
        </div>
    </div>

    {{-- BOTÓN DE ENVÍO — único, al final --}}
    <div class="row mt-4 mb-5">
        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('turnos.index') }}" class="btn btn-secondary">
                Cancelar
            </a>
            <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="bi bi-floppy me-1"></i> Guardar turno
            </button>
        </div>
    </div>

</form>

@endsection

Paso 7 — Agregar name a los inputs estáticos de ventas y surtidores
Los partials de ventas y surtidores tienen filas fijas (no dinámicas), así que necesitan name con índice hardcodeado. Ejemplo en ventas.blade.php:
blade{{-- Fila SURTIDOR 1 CTE --}}
<input type="hidden" name="ventas[0][surtidor]"    value="SURTIDOR 1 CTE">
<input type="hidden" name="ventas[0][combustible]" value="corriente">
<input type="text"   name="ventas[0][galones]"
       class="form-control form-control-sm erp-input galones-input galones-cte"
       data-precio="{{ config('combustibles.corriente') }}">
<input type="text"   name="ventas[0][valor]"
       class="form-control form-control-sm erp-input valor-total" readonly>

{{-- Fila SURTIDOR 1 ACPM --}}
<input type="hidden" name="ventas[1][surtidor]"    value="SURTIDOR 1 ACPM">
<input type="hidden" name="ventas[1][combustible]" value="acpm">
{{-- ... y así con todas las filas --}}
El mismo patrón aplica para surtidores.blade.php con los nombres de manguera (PLUS 01, ACPM 03, etc.).

Resumen de decisiones
PreguntaRespuesta¿Un form o varios?Un único <form> en la plantilla principal¿Un botón o varios?Un único botón "Guardar turno" al final del form¿Los partials tienen su propio submit?No, son solo fragmentos HTML dentro del form¿Dónde va el CSRF?Una sola vez en el form principal¿El resumen se guarda en BD?No, es calculado en JS. Solo se persisten los datos fuente¿Cómo se limpian los números con puntos de miles?Con parseNumber() en el controlador antes de guardarDijiste: que puede pasar si ya tengo creada esta migracion
