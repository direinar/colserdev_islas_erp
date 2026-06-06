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
for ($i = 41; $i <= 120; $i++) {
    $row = $rows[$i] ?? [];
    if (!$row) {
        continue;
    }
    echo 'ROW ' . $i . PHP_EOL;
    foreach ($row as $col => $val) {
        if ($val !== null && trim((string)$val) !== '') {
            echo $col . ': ' . trim((string)$val) . PHP_EOL;
        }
    }
    echo '---' . PHP_EOL;
}
