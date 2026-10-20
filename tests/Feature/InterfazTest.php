<?php

use App\Models\User;

/**
 * Reglas de interfaz comunes a todas las pantallas: un solo título principal (<h1>, que da
 * el encabezado común), cada etiqueta asociada a un campo que existe y sin ids repetidos.
 */
test('cada pantalla tiene un solo título y sus etiquetas apuntan a campos existentes', function (string $ruta) {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();

    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//h1')->length)->toBe(1);

    $ids = array_map(fn (DOMElement $nodo): string => $nodo->getAttribute('id'), iterator_to_array($xpath->query('//*[@id]')));
    expect(array_diff_assoc($ids, array_unique($ids)))->toBe([]);

    foreach ($xpath->query('//label') as $etiqueta) {
        /** @var DOMElement $etiqueta */
        $para = $etiqueta->getAttribute('for');
        $envuelveCampo = $xpath->query('.//input|.//select|.//textarea', $etiqueta)->length > 0;

        expect($para !== '' || $envuelveCampo)->toBeTrue('Etiqueta sin campo: '.trim($etiqueta->textContent));

        if ($para !== '') {
            expect(in_array($para, $ids, true))->toBeTrue("La etiqueta '{$para}' no tiene un campo con ese id");
        }
    }
})->with([
    'dashboard', 'turnos.create', 'turnos.pendientes',
    'cartera.index', 'cartera-saldos-iniciales.index', 'customers.index', 'customers.create',
    'compras.create', 'compras-lubricantes.create', 'comprobante-contable-compras.create', 'anticipo-bimestral.create',
    'inventarios-gasolina.create', 'inventarios-acpm.create', 'inventarios-lubricantes.create', 'inventarios-lubricantes.control',
    'fuel-prices.index', 'fuel-prices.create', 'lubricants.index', 'lubricants.create',
    'bancos.index', 'bancos.create', 'proveedores.index', 'proveedores.create', 'users.index', 'users.create',
    'control-tanques.index', 'informes.control-caja.index', 'informes.recaudos-cartera.index', 'informes.saldos.index',
    'informes.comprobante-contable-compras.index', 'informes.comprobante-ventas.index', 'informes.comprobante-recaudos.index', 'informes.margen-mayorista.index', 'inventarios-saldos-iniciales.index',
    'informes.anticipo-bimestral.index', 'informes.cierre.index',
]);

test('el menú tiene los nombres acordados y Compras ya no muestra los registros manuales', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $html = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();

    $inicioInformes = strpos($html, '>
                                Informes');
    $inicioCompras = strpos($html, '>
                                Compras');
    $menuInformes = substr($html, $inicioInformes, strpos($html, '</ul>', $inicioInformes) - $inicioInformes);
    $menuCompras = substr($html, $inicioCompras, strpos($html, '</ul>', $inicioCompras) - $inicioCompras);

    expect($menuInformes)->not->toContain('(manual)')
        ->and($menuInformes)->toContain(route('informes.cierre.index'))
        ->and($menuInformes)->toContain('Informes de gestión')
        ->and($menuInformes)->toContain('Control de caja')
        ->and($menuInformes)->toContain(route('informes.comprobante-recaudos.index'))
        // Margen mayorista en Operación, antes de Cartera y saldos.
        ->and(strpos($menuInformes, route('informes.margen-mayorista.index')))->toBeLessThan(strpos($menuInformes, 'Cartera y saldos'))
        ->and($menuCompras)->toContain('Compras de canastilla')
        ->and($menuCompras)->not->toContain(route('anticipo-bimestral.create'))
        ->and($menuCompras)->not->toContain(route('comprobante-contable-compras.create'))
        ->and($menuCompras)->not->toContain('Registros manuales')
        ->and($html)->toContain('Precios de canastilla')
        ->and($html)->toContain('Bancos recaudadores');
});
