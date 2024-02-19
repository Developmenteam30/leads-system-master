<?php

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorksheetHelper extends Worksheet
{
    /**
     * Fill worksheet from values in array.
     * A safer version of the parent method that handles values beginning with a "=" as to not treat them as a formula.
     *
     * @param  array  $source  Source array
     * @param  mixed  $nullValue  Value in source array that stands for blank cell
     * @param  string  $startCell  Insert array starting from this cell address as the top left coordinate
     * @param  bool  $strictNullComparison  Apply strict comparison when testing for null values in the array
     *
     * @return $this
     * @throws Exception
     */
    public function fromArray(array $source, $nullValue = null, $startCell = 'A1', $strictNullComparison = false): static
    {
        //    Convert a 1-D array to 2-D (for ease of looping)
        if (!is_array(end($source))) {
            $source = [$source];
        }

        // start coordinate
        [$startColumn, $startRow] = Coordinate::coordinateFromString($startCell);

        // Loop through $source
        foreach ($source as $rowData) {
            $currentColumn = $startColumn;
            foreach ($rowData as $cellValue) {
                if ($strictNullComparison) {
                    if ($cellValue !== $nullValue) {
                        // Set cell value
                        $this->getCell($currentColumn.$startRow)->setValue((str_starts_with($cellValue, '=') ? "'".$cellValue : $cellValue));
                    }
                } else {
                    if ($cellValue != $nullValue) {
                        // Set cell value
                        $this->getCell($currentColumn.$startRow)->setValue((str_starts_with($cellValue, '=') ? "'".$cellValue : $cellValue));
                    }
                }
                ++$currentColumn;
            }
            ++$startRow;
        }

        return $this;
    }

    public function setCellValueAndFormatByColumnAndRow($colCnt, $rowCnt, $value, $format): static
    {
        $this->setCellValue([$colCnt, $rowCnt], $value);
        $this->getCell([$colCnt, $rowCnt])
            ->getStyle()
            ->getNumberFormat()
            ->setFormatCode($format);

        return $this;
    }
}
