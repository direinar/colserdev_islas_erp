<?php

use App\Livewire\PlanillaTurno;
use App\Models\Turno;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    if (! Schema::hasTable('turnos')) {
        Schema::create('turnos', function ($table) {
            $table->id();
            $table->date('fecha');
            $table->unsignedSmallInteger('numero_turno');
            $table->string('nombre_vendedor')->nullable();
            $table->string('revisado_por')->nullable();
            $table->decimal('precio_corriente', 12, 2)->default(0);
            $table->decimal('precio_acpm', 12, 2)->default(0);
            $table->decimal('traslado_sobrante', 14, 2)->default(0);
            $table->decimal('traslado_faltante', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['fecha', 'numero_turno']);
        });
    }

    if (! Schema::hasTable('turno_surtidores')) {
        Schema::create('turno_surtidores', function ($table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('manguera');
            $table->string('combustible');
            $table->decimal('lectura_inicial', 12, 3)->default(0);
            $table->decimal('lectura_final', 12, 3)->default(0);
            $table->decimal('galones', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    Turno::query()->delete();
});

test('new turno loads previous final readings as initial values', function () {
    $previousTurno = Turno::create([
        'fecha' => now()->toDateString(),
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'precio_corriente' => 16.5,
        'precio_acpm' => 9.85,
    ]);

    $previousTurno->surtidores()->createMany([
        ['manguera' => 'PLUS O1', 'combustible' => 'CTE', 'lectura_inicial' => 0, 'lectura_final' => 1500, 'galones' => 1500],
        ['manguera' => 'PLUS O2', 'combustible' => 'CTE', 'lectura_inicial' => 0, 'lectura_final' => 1250, 'galones' => 1250],
        ['manguera' => 'ACPM O3', 'combustible' => 'ACPM', 'lectura_inicial' => 0, 'lectura_final' => 980, 'galones' => 980],
        ['manguera' => 'ACPM O4', 'combustible' => 'ACPM', 'lectura_inicial' => 0, 'lectura_final' => 1015, 'galones' => 1015],
        ['manguera' => 'PLUS O5', 'combustible' => 'CTE', 'lectura_inicial' => 0, 'lectura_final' => 1100, 'galones' => 1100],
        ['manguera' => 'PLUS O6', 'combustible' => 'CTE', 'lectura_inicial' => 0, 'lectura_final' => 1205, 'galones' => 1205],
        ['manguera' => 'ACPM O7', 'combustible' => 'ACPM', 'lectura_inicial' => 0, 'lectura_final' => 805, 'galones' => 805],
        ['manguera' => 'ACPM O8', 'combustible' => 'ACPM', 'lectura_inicial' => 0, 'lectura_final' => 910, 'galones' => 910],
        ['manguera' => 'PLUS O9', 'combustible' => 'CTE', 'lectura_inicial' => 0, 'lectura_final' => 1300, 'galones' => 1300],
        ['manguera' => 'PLUS 10', 'combustible' => 'CTE', 'lectura_inicial' => 0, 'lectura_final' => 1410, 'galones' => 1410],
        ['manguera' => 'ACPM 11', 'combustible' => 'ACPM', 'lectura_inicial' => 0, 'lectura_final' => 875, 'galones' => 875],
        ['manguera' => 'ACPM 12', 'combustible' => 'ACPM', 'lectura_inicial' => 0, 'lectura_final' => 930, 'galones' => 930],
    ]);

    $component = Livewire::test(PlanillaTurno::class);

    expect($component->get('lecturas.0.inicial'))->toBe(1500.0)
        ->and($component->get('lecturas.1.inicial'))->toBe(1250.0)
        ->and($component->get('lecturas.2.inicial'))->toBe(980.0)
        ->and($component->get('lecturas.3.inicial'))->toBe(1015.0)
        ->and($component->get('lecturas.4.inicial'))->toBe(1100.0)
        ->and($component->get('lecturas.5.inicial'))->toBe(1205.0)
        ->and($component->get('lecturas.6.inicial'))->toBe(805.0)
        ->and($component->get('lecturas.7.inicial'))->toBe(910.0)
        ->and($component->get('lecturas.8.inicial'))->toBe(1300.0)
        ->and($component->get('lecturas.9.inicial'))->toBe(1410.0)
        ->and($component->get('lecturas.10.inicial'))->toBe(875.0)
        ->and($component->get('lecturas.11.inicial'))->toBe(930.0);
});
