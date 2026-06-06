<?php
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$file = __DIR__ . '/docs/PLANTILLA PARA PROGRAMA.xlsm';
$type = IOFactory::identify($file);
echo 'TYPE: ' . $type . PHP_EOL;
$reader = IOFactory::createReader($type);
if (method_exists($reader, 'listWorksheetNames')) {
    print_r($reader->listWorksheetNames($file));
} else {
    echo 'no listWorksheetNames' . PHP_EOL;
}
