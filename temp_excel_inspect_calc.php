<?php
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$file = __DIR__ . '/docs/PLANTILLA PARA PROGRAMA.xlsm';
$type = IOFactory::identify($file);
$reader = IOFactory::createReader($type);
$reader->setReadDataOnly(false);
$reader->setLoadSheetsOnly(['PLANILLA DE TURNOS']);
$spreadsheet = $reader->load($file);
$sheet = $spreadsheet->getSheetByName('PLANILLA DE TURNOS');
$cells = ['J1','L1','C5','D5','C6','D6','C32','D32','C74','D74','H30','H31','H32','H42','H48','H56','H57','C105','D105','E105'];
foreach ($cells as $cell) {
    $value = $sheet->getCell($cell)->getValue();
    $calculated = $sheet->getCell($cell)->getCalculatedValue();
    echo $cell . ' = raw: ' . trim((string)$value) . ' | calc: ' . trim((string)$calculated) . PHP_EOL;
}
