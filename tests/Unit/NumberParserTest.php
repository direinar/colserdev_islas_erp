<?php

use App\Support\NumberParser;

test('decimal lee el formato es-CO: punto de miles y coma decimal', function (mixed $entrada, float $esperado) {
    expect(NumberParser::decimal($entrada))->toBe($esperado);
})->with([
    'miles' => ['3.000', 3000.0],
    'miles y decimales' => ['1.500,25', 1500.25],
    'millones' => ['83.974.669', 83974669.0],
    'solo decimales' => ['0,5', 0.5],
    'con espacios' => [' 2.000 ', 2000.0],
    'vacío' => ['', 0.0],
    'nulo' => [null, 0.0],
    'texto' => ['abc', 0.0],
]);

test('quantity, money y decimal siguen convenciones distintas a propósito', function () {
    // Galones y lecturas de la planilla: punto decimal.
    expect(NumberParser::quantity('1.250'))->toBe(1.25)
        // Dinero sin decimales: cualquier separador es de miles.
        ->and(NumberParser::money('1.250'))->toBe(1250.0)
        // Compras, cartera y anticipos (es-CO): punto de miles.
        ->and(NumberParser::decimal('1.250'))->toBe(1250.0);
});

test('wholeQuantity lee el punto seguido de tres cifras como miles en galones enteros', function (string $entrada, float $esperado) {
    expect(NumberParser::wholeQuantity($entrada))->toBe($esperado);
})->with([
    'punto de miles' => ['3.800', 3800.0],
    'sin separador' => ['2500', 2500.0],
    'miles y decimales' => ['3.800,5', 3800.5],
    'decimal con coma' => ['3800,5', 3800.5],
    'decimal con punto' => ['3800.5', 3800.5],
    'vacío' => ['', 0.0],
]);
