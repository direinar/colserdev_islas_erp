<?php

/**
 * Datos del empleador para desprendibles, certificados y liquidaciones de nómina.
 */
return [
    'empresa' => env('NOMINA_EMPRESA', 'BYH AGROCOMERCIAL SAS'),
    'nit' => env('NOMINA_EMPRESA_NIT', ''),
    'establecimiento' => env('NOMINA_ESTABLECIMIENTO', 'EDS El Portal de Tunja'),
    'ciudad' => env('NOMINA_CIUDAD', 'Tunja'),
    'direccion' => env('NOMINA_DIRECCION', ''),
    'telefono' => env('NOMINA_TELEFONO', ''),
    'firma_nombre' => env('NOMINA_FIRMA_NOMBRE', ''),
    'firma_cargo' => env('NOMINA_FIRMA_CARGO', 'Representante legal'),
];
