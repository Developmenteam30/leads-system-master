<?php

namespace App\Http\Controllers\Api\Reports;

use App\Datasets\AttendanceDetailDataset;
use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Responses\ErrorResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class AttendanceDetailController extends BaseController
{
    /**
     * Load a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|bail|date',
            'search' => 'bail|string|nullable',
            'statuses' => 'bail|string|nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $date = CarbonImmutable::parse($request->input('date'));

        $filters = [
            'date' => $date,
            'search' => $request->input('search'),
            'statuses' => $request->input('statuses'),
        ];

        AuditLog::createFromRequest($request, 'REPORT:ATTENDANCE-DETAIL', array_merge($filters, [
            'date' => $date->format('Y-m-d'),
        ]));

        $items = AttendanceDetailDataset::getDailyValues($filters);

        $allow_list = [
            'id',
            'agent_name',
            'first_call_time',
            'last_call_time',
            'status',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'Agent ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Agent Name', 'field' => 'agent_name',],
                ['label' => 'First Call Time', 'field' => 'first_call_time', 'displayFormat' => DataTableFields::TIME_12HOUR,],
                ['label' => 'Last Call Time', 'field' => 'last_call_time', 'displayFormat' => DataTableFields::TIME_12HOUR,],
                ['label' => 'Status', 'field' => 'status',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Attendance Detail {$date->format('Y-m-d')}.xlsx");
    }
}
