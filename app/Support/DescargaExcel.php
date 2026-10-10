<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga de un libro de Excel (.xlsx) generado con PhpSpreadsheet, sin escribirlo en disco.
 */
class DescargaExcel
{
    public static function respuesta(Spreadsheet $libro, string $archivo): StreamedResponse
    {
        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            $archivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
