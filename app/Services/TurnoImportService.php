<?php

namespace App\Services;

use App\DTOs\TurnoDTO;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TurnoImportService
{
    /**
     * Import a turno from an Excel file. Returns a TurnoDTO.
     * Requires phpoffice/phpspreadsheet.
     */
    public function import(string $filePath, string $sheetName = 'planilla de turnos'): TurnoDTO
    {
        if (!class_exists(IOFactory::class)) {
            throw new \RuntimeException("PhpSpreadsheet not found. Run: composer require phpoffice/phpspreadsheet");
        }

        $type = IOFactory::identify($filePath);
        $reader = IOFactory::createReader($type);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        $availableSheets = [];
        if (method_exists($reader, 'listWorksheetNames')) {
            $availableSheets = $reader->listWorksheetNames($filePath);
        }

        $selectedSheetName = null;
        foreach ($availableSheets as $name) {
            if (trim(strtolower($name)) === trim(strtolower($sheetName))) {
                $selectedSheetName = $name;
                break;
            }
        }

        if ($selectedSheetName === null && !empty($availableSheets)) {
            $selectedSheetName = $availableSheets[0];
        }

        if ($selectedSheetName !== null && method_exists($reader, 'setLoadSheetsOnly')) {
            $reader->setLoadSheetsOnly([$selectedSheetName]);
        }

        $spreadsheet = $reader->load($filePath);
        $sheet = null;
        if ($selectedSheetName !== null) {
            $sheet = $spreadsheet->getSheetByName($selectedSheetName);
        }

        if (!$sheet) {
            $sheet = $spreadsheet->getSheet(0);
        }

        $rows = $sheet->toArray(null, false, true, true);

        // Simple heuristic: first non-empty row is header
        $header = [];
        $dataRows = [];
        foreach ($rows as $row) {
            $empty = true;
            foreach ($row as $cell) {
                if ($cell !== null && trim($cell) !== '') {
                    $empty = false;
                    break;
                }
            }
            if ($empty) {
                continue;
            }

            if (empty($header)) {
                // use this row as header
                $header = array_values(array_map(function ($v) {
                    return trim(strtolower($v));
                }, $row));
                continue;
            }

            $values = array_values($row);
            $mapped = [];
            foreach ($header as $i => $h) {
                $mapped[$h] = $values[$i] ?? null;
            }
            $dataRows[] = $mapped;
        }

        // Return DTO with raw rows under key 'rows' and meta
        return new TurnoDTO([
            'source_file' => $filePath,
            'sheet' => $sheet->getTitle(),
            'rows' => $dataRows,
        ]);
    }
}
