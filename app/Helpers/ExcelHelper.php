<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelHelper
{
    const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    // Use our own constants modeled after PhpOffice\PhpSpreadsheet\Style\NumberFormat
    // because PHPSpreadsheet sometimes changes their constants without warning.
    const FORMAT_CURRENCY_USD_SIMPLE = '"$"#,##0.00_-';
    const FORMAT_ACCOUNTING_USD = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';
    const FORMAT_TEXT = '@';
    const FORMAT_NUMBER_COMMA_SEPARATED2 = '#,##0.00_-';
    const FORMAT_PERCENTAGE_00 = '0.00%';
    const FORMAT_DATE_TIME4 = 'h:mm:ss';
    const FORMAT_DATE_MDYSLASH = 'm/d/yyyy';
    const FORMAT_DATE_TIME2 = 'h:mm:ss AM/PM';

    public static function boldTotalRow($worksheet): void
    {
        // Bold the total row
        $worksheet->getStyle('A'.$worksheet->getHighestRow().':'.$worksheet->getHighestColumn().$worksheet->getHighestRow())->getFont()->setBold(true);
    }

    public static function topRowFormat($worksheet): void
    {
        // Cell alignment and centering
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().$worksheet->getHighestRow())->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Background fill and bold the top row
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'1')->getFont()->setBold(true);
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF072F5F');
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'1')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);

        // Auto filter columns
        $worksheet->setAutoFilter('A1:'.$worksheet->getHighestColumn().$worksheet->getHighestRow());

        // Auto size columns
        foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Freeze the top row and first column
        $worksheet->freezePane('B2');

        // Reset selected cell
        $worksheet->setSelectedCell('B2');
    }

    public static function topRowSubheaderFormat($worksheet): void
    {
        // Cell alignment and centering
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().$worksheet->getHighestRow())->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Background fill and bold the top row
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'2')->getFont()->setBold(true);
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF072F5F');
        $worksheet->getStyle('A1:'.$worksheet->getHighestColumn().'2')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);

        // Auto filter columns
        $worksheet->setAutoFilter('A2:'.$worksheet->getHighestColumn().$worksheet->getHighestRow());

        // Auto size columns
        foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Freeze the top row and first column
        $worksheet->freezePane('B3');

        // Reset selected cell
        $worksheet->setSelectedCell('B3');
    }

    public static function displayFormatter(WorksheetHelper $worksheet, $colCnt, $rowCnt, $value, $display_format): void
    {
        switch ($display_format) {
            case 'accounting':
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($value) && $value !== 0 ? $value : '', self::FORMAT_ACCOUNTING_USD);
                break;

            case 'boolean_icon':
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($value) ? 'Y' : 'N', self::FORMAT_TEXT);
                break;

            case 'currency':
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($value) && $value !== 0 ? $value : '', self::FORMAT_CURRENCY_USD_SIMPLE);
                break;

            case DataTableFields::DATE_YYYYMMDD:
                try {
                    $value = !empty($value) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($value)) : '';
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, $value, self::FORMAT_DATE_MDYSLASH);
                } catch (InvalidFormatException $e) {
                    $worksheet->setCellValue([$colCnt++, $rowCnt], $value);
                }
                break;

            case DataTableFields::DATETIME_YYYYMMDD:
                try {
                    $value = !empty($value) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($value)->setTimeZone(config('settings.timezone.local'))) : '';
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, $value, self::FORMAT_DATE_MDYSLASH);
                } catch (InvalidFormatException $e) {
                    $worksheet->setCellValue([$colCnt++, $rowCnt], $value);
                }
                break;

            case 'number':
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($value) && $value !== 0 ? $value : '', self::FORMAT_NUMBER_COMMA_SEPARATED2);
                break;

            case 'integer':
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($value) && $value !== 0 ? $value : '', '#,##0');
                break;

            case 'percentage':
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($value) && $value !== 0 ? ($value / 100) : '', self::FORMAT_PERCENTAGE_00);
                break;

            case 'sec2time':
                try {
                    $value = !empty($value) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($value)) : '';
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, $value, self::FORMAT_DATE_TIME4);
                } catch (InvalidFormatException $e) {
                    $worksheet->setCellValue([$colCnt++, $rowCnt], $value);
                }
                break;

            case 'text':
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($value) ? $value : '', self::FORMAT_TEXT);
                break;

            case DataTableFields::TIME_12HOUR:
                try {
                    $value = !empty($value) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($value)) : '';
                    $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, $value, self::FORMAT_DATE_TIME2);
                } catch (InvalidFormatException $e) {
                    $worksheet->setCellValue([$colCnt++, $rowCnt], $value);
                }
                break;

            case DataTableFields::VOTE_YES:
                $worksheet->setCellValueAndFormatByColumnAndRow($colCnt++, $rowCnt, !empty($value) ? 'Y' : '', self::FORMAT_TEXT);
                break;

            default:
                $worksheet->setCellValue([$colCnt++, $rowCnt], $value);
                break;
        }

    }


}
