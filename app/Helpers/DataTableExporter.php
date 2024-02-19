<?php

namespace App\Helpers;

use App\Responses\ErrorResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataTableExporter
{
    public static function export($datatable, $filename)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            $worksheet = new WorksheetHelper($spreadsheet, 'Export');
            $spreadsheet->addSheet($worksheet, 0);

            $rowCnt = 0;

            if (isset($datatable['subColumns'])) {
                $rowCnt++;
                $colCnt = 1;
                foreach ($datatable['subColumns'] as $column) {
                    $worksheet->setCellValue([$colCnt, $rowCnt], $column['label'] ?? '');
                    $colSpan = $column['colSpan'] ?? 1;
                    $colCnt += $colSpan;
                    $worksheet->mergeCells(Coordinate::stringFromColumnIndex($colCnt - $colSpan).$rowCnt.':'.Coordinate::stringFromColumnIndex($colCnt - 1).$rowCnt);
                }
            }

            $rowCnt++;
            $colCnt = 1;
            foreach ($datatable['columns'] as $column) {
                $worksheet->setCellValue([$colCnt++, $rowCnt], $column['label'] ?? '');
            }

            foreach ($datatable['rows'] as $row) {
                $colCnt = 1;
                $rowCnt++;
                foreach ($datatable['columns'] as $column) {
                    ExcelHelper::displayFormatter($worksheet, $colCnt++, $rowCnt, $row[$column['field']] ?? '', $column['displayFormat'] ?? '');
                }
            }

            foreach ($datatable['totals'] as $row) {
                $colCnt = 1;
                $rowCnt++;
                foreach ($datatable['columns'] as $column) {
                    ExcelHelper::displayFormatter($worksheet, $colCnt++, $rowCnt, $row[$column['field']] ?? '', $column['displayFormat'] ?? '');
                }
            }

            // Bold the total row
            if ($datatable['totals']->isNotEmpty()) {
                ExcelHelper::boldTotalRow($worksheet);
            }

            if (isset($datatable['subColumns'])) {
                ExcelHelper::topRowSubheaderFormat($worksheet);
            } else {
                ExcelHelper::topRowFormat($worksheet);
            }

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => ExcelHelper::CONTENT_TYPE,
            ]);
        } catch (\Throwable $e) {
            return ErrorResponse::json("Exception: {$e->getMessage()}", 400);
        }

    }
}
