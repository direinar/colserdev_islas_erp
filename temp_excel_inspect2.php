<?php
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$file = __DIR__ . '/docs/PLANTILLA PARA PROGRAMA.xlsm';
$type = IOFactory::identify($file);
$reader = IOFactory::createReader($type);
$reader->setReadDataOnly(true);
$reader->setLoadSheetsOnly(['PLANILLA DE TURNOS']);
$spreadsheet = $reader->load($file);
$sheet = $spreadsheet->getSheetByName('PLANILLA DE TURNOS');
$rows = $sheet->toArray(null, false, true, true);
$limit = 40;
foreach (array_slice($rows, 0, $limit) as $i => $row) {
    echo 'ROW ' . ($i + 1) . PHP_EOL;
    foreach ($row as $col => $val) {
        echo $col . ': ' . trim((string)$val) . PHP_EOL;
    }
    echo '---' . PHP_EOL;
}
