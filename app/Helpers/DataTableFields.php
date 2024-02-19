<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DataTableFields
{
    const BOOLEAN_ICON_YES = 'boolean_icon_yes';
    const DATE_YYYYMMDD = 'date_YYYYMMDD';
    const DATETIME_YYYYMMDD = 'datetime_YYYYMMDD';
    const TIME_12HOUR = 'time_HHMMSSA';
    const VOTE_YES = 'vote_yes';

    public static function getByAllowList($datatable, $allow_list, $format = true): array
    {
        $columns = collect($datatable['columns'] ?? []);
        $subColumns = collect($datatable['subColumns'] ?? []);
        $rows = collect($datatable['rows'] ?? []);
        $totals = collect($datatable['totals'] ?? []);
        $counts = collect($datatable['counts'] ?? []);

        // Old logic = supply an explicit $allow_list of columns
        // New logic = if $allow_list is false, then use the 'show' attribute of the columns array
        if (false === $allow_list) {
            $columns = $columns->filter(function ($column) {
                return !isset($column['show']) || true === $column['show'];
            })->values();
            $allow_list = $columns->pluck('field')->toArray();
        } else {
            $columns = $columns->whereIn('field', $allow_list)->values();
        }

        $rows->transform(function ($row) use ($allow_list, $columns, $subColumns, $format) {
            // Convert any Eloquent models into an array
            if ($row instanceof Collection || $row instanceof Model) {
                $row = $row->toArray();
            }

            foreach ($row as $key => &$value) {
                if (!in_array($key, $allow_list)) {
                    unset($row[$key]);
                } elseif ($format) {
                    $columnDefinition = $columns->where('field', $key)->first();
                    if (!empty($columnDefinition['displayFormat'])) {
                        $value = self::getFormattedValue($value, $columnDefinition['displayFormat']);
                    }
                }
            }

            return $row;
        });

        $totals->transform(function ($row) use ($allow_list, $columns, $subColumns, $format) {
            foreach ($row as $key => &$value) {
                if (!in_array($key, $allow_list)) {
                    unset($row[$key]);
                } elseif ($format) {
                    $columnDefinition = $columns->where('field', $key)->first();
                    if (!empty($columnDefinition['displayFormat'])) {
                        $value = self::getFormattedValue($value, $columnDefinition['displayFormat']);
                    }
                }
            }

            return $row;
        });

        return [
            'columns' => $columns,
            'subColumns' => $subColumns,
            'rows' => $rows,
            'totals' => $totals,
            'counts' => $counts,
        ];
    }

    public static function displayOrExport($datatable, $allow_list, $request, $filename): \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $request->filled('export') ?
            DataTableExporter::export(DataTableFields::getByAllowList($datatable, $allow_list, false), $filename) :
            response()->json(DataTableFields::getByAllowList($datatable, $allow_list));
    }

    private static function getFormattedValue($value, $displayFormat)
    {
        switch ($displayFormat) {
            case 'boolean_icon':
                $value = !empty($value) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                break;

            case self::BOOLEAN_ICON_YES:
                $value = !empty($value) ? '<i class="fas fa-check text-success"></i>' : '';
                break;

            case 'accounting':
            case 'currency':
                $value = !empty($value) ? '$'.number_format($value, 2) : '';
                break;

            // Use when a date field is coming from the database (no timezone conversion done)
            case self::DATE_YYYYMMDD:
                try {
                    $value = !empty($value) && '0000-00-00' !== $value ? CarbonImmutable::parse($value)->format('Y-m-d') : '';
                } catch (InvalidFormatException $e) {
                    // Do nothing
                }
                break;

            // Use when a UTC datetime or timestamp field is coming from the database (timezone conversion done)
            case self::DATETIME_YYYYMMDD:
                try {
                    $value = !empty($value) ? CarbonImmutable::parse($value)->setTimeZone(config('settings.timezone.local'))->format('Y-m-d') : '';
                } catch (InvalidFormatException $e) {
                    // Do nothing
                }
                break;

            case 'number':
                $value = !empty($value) ? number_format($value, 2) : '';
                break;

            case 'integer':
                $value = !empty($value) ? number_format($value) : '';
                break;

            case 'sec2time':
                $value = DateTimeHelper::seconds2time($value);
                break;

            case self::VOTE_YES:
                $value = !empty($value) ? '<i class="fas fa-vote-yea"></i>' : '';
                break;
        }

        return $value;
    }
}
