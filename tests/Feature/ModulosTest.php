<?php

use App\Models\User;

test('administracion ve el selector con los tres modulos y cada uno muestra su menu', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->get(route('dashboard'))->assertOk()
        ->assertSeeInOrder(['OPERACIONES', 'NÓMINA', 'CONTABILIDAD'])
        ->assertSee('<a href="'.route('dashboard').'" class="selector-modulo activo"', false)
        ->assertSee('Planilla de turnos')
        ->assertSee('Conteo');

    $this->actingAs($admin)->get(route('nomina.dashboard'))->assertOk()
        ->assertSee('<a href="'.route('nomina.dashboard').'" class="selector-modulo activo"', false)
        ->assertSee(route('nomina.empleados.index'))
        ->assertSee('Parámetros de liquidación')
        // El menú de Operaciones no se muestra dentro de Nómina.
        ->assertDontSee(route('conteos.index'));

    $this->actingAs($admin)->get(route('contabilidad.index'))->assertOk()->assertSee('Módulo en construcción');
});

test('gerente y super administrador tambien entran a Nomina y Contabilidad', function (string $rol) {
    $usuario = User::factory()->create(['role' => $rol]);

    $this->actingAs($usuario)->get(route('nomina.dashboard'))->assertOk()
        ->assertSeeInOrder(['OPERACIONES', 'NÓMINA', 'CONTABILIDAD']);
    $this->actingAs($usuario)->get(route('contabilidad.index'))->assertOk();
})->with([User::ROLE_GERENTE, User::ROLE_SUPERADMIN]);

test('el contador por ahora no entra a ningun modulo y ve un aviso', function () {
    $contador = User::factory()->create(['role' => User::ROLE_CONTADOR]);

    expect($contador->modulos())->toBe([]);
    $this->actingAs($contador)->get(route('dashboard'))->assertOk()
        ->assertSee('Su usuario no tiene módulos asignados')
        ->assertDontSee('selector-modulos', false);

    foreach (['nomina.dashboard', 'nomina.empleados.index', 'contabilidad.index', 'turnos.create', 'conteos.index', 'users.index'] as $ruta) {
        $this->actingAs($contador)->get(route($ruta))->assertForbidden();
    }
});

test('isleros y jefe de patios no ven el selector ni entran a Nomina ni Contabilidad', function (string $rol) {
    $usuario = User::factory()->create(['role' => $rol]);

    $this->actingAs($usuario)->get(route('turnos.create'))->assertOk()
        ->assertDontSee('selector-modulos', false)
        ->assertDontSee('NÓMINA');
    $this->actingAs($usuario)->get(route('nomina.dashboard'))->assertForbidden();
    $this->actingAs($usuario)->get(route('nomina.empleados.index'))->assertForbidden();
    $this->actingAs($usuario)->get(route('contabilidad.index'))->assertForbidden();
})->with([User::ROLE_ISLERO, User::ROLE_JEFE_PATIOS]);

test('administracion puede crear usuarios con el rol contador', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    expect($admin->rolesAsignables())->toContain(User::ROLE_CONTADOR)
        ->and(User::etiquetaRol(User::ROLE_CONTADOR))->toBe('Contador');
});
