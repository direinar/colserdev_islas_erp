<?php

use App\Support\TerceroComprobante;

test('una linea sin NIT va con tercero GENERAL y el NIT en blanco, aunque traiga nombre', function (string $tercero, string $nit, string $terceroEsperado, string $nitEsperado) {
    $linea = TerceroComprobante::aplicar(['cuenta' => '13050501', 'tercero' => $tercero, 'nit' => $nit, 'debito' => 100.0]);

    expect($linea)->toBe(['cuenta' => '13050501', 'tercero' => $terceroEsperado, 'nit' => $nitEsperado, 'debito' => 100.0]);
})->with([
    'sin tercero ni NIT' => ['', '', 'GENERAL', ''],
    'cliente sin documento' => ['SIN CLIENTE', '', 'GENERAL', ''],
    'NIT con espacios' => ['PROVEEDOR X', '  ', 'GENERAL', ''],
    'con NIT se conserva' => ['CONSORCIO LIDER', '900111222', 'CONSORCIO LIDER', '900111222'],
]);
