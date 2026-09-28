<?php

use App\Models\User;

test('en pantallas pequenas el menu se abre como panel lateral con el boton de menu', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $html = $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-bs-toggle="offcanvas" data-bs-target="#menu-principal"', false)
        ->assertSee('class="offcanvas offcanvas-end offcanvas-lg navbar-offcanvas"', false)
        ->assertSee('data-bs-dismiss="offcanvas"', false)
        ->getContent();

    $menu = str($html)->after('id="menu-principal"')->before('</nav>')->toString();

    // Los menús y el usuario van dentro del panel, y ya no se fuerzan en una sola fila.
    expect($menu)->toContain('Operación', 'Comercial', 'Inventario', 'Cerrar sesión')
        ->and($menu)->not->toContain('flex-row align-items-center');
});
