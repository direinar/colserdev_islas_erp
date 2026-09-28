<?php

use App\Models\User;

test('las paginas del erp y el login enlazan el manifiesto de la pwa', function () {
    $this->get(route('login'))->assertOk()->assertSee('<link rel="manifest"', false);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<link rel="manifest"', false)
        ->assertSee('<meta name="theme-color" content="#d40000">', false)
        ->assertSee('data-pwa-install-button', false);
});

test('el manifiesto cumple lo que exige el navegador para instalar la aplicacion', function () {
    $manifiesto = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifiesto)->toMatchArray([
        'short_name' => 'ByH ERP',
        'start_url' => '/dashboard',
        'display' => 'standalone',
    ]);

    $iconos = collect($manifiesto['icons'])->keyBy(fn (array $icono): string => $icono['sizes'].'-'.$icono['purpose']);

    expect($iconos->keys()->all())->toContain('192x192-any', '512x512-any', '512x512-maskable');

    foreach ($iconos as $icono) {
        [$ancho, $alto] = getimagesize(public_path(ltrim($icono['src'], '/')));

        expect("{$ancho}x{$alto}")->toBe($icono['sizes']);
    }

    expect(file_exists(public_path('sw.js')))->toBeTrue()
        ->and(file_get_contents(public_path('sw.js')))->toContain("'/offline.html'")
        ->and(file_exists(public_path('offline.html')))->toBeTrue();
});
