<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerOnscriptRecordingUploadLog;
use App\Models\DialerPettyCashEntry;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OnScriptUploadLogsController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'bail|nullable|date',
            'end_date' => 'bail|nullable|date',
            'statuses' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $statuses = explode(',', $request->input('statuses', ''));

        AuditLog::createFromRequest($request, 'REPORT:ONSCRIPT-UPLOAD-LOGS', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'statuses' => $request->input('agent_type_ids'),
        ]);

        $items = DialerOnscriptRecordingUploadLog::query()
            ->join('auditlog', 'auditlog.logId', 'dialer_onscript_recording_upload_logs.log_id')
            ->join('dialer_logs', 'dialer_logs.call_id', 'dialer_onscript_recording_upload_logs.call_id')
            ->select([
                'dialer_onscript_recording_upload_logs.id',
                'dialer_onscript_recording_upload_logs.call_id',
                'dialer_onscript_recording_upload_logs.onscript_id',
                'dialer_onscript_recording_upload_logs.status',
                'dialer_logs.time_stamp',
                'auditlog.timestamp',
            ])
            ->when(sizeof($statuses) == 1 && in_array('1', $statuses), function ($query) {
                $query->where('dialer_onscript_recording_upload_logs.status', '=', 'processing');
            })
            ->when(sizeof($statuses) == 1 && in_array('0', $statuses), function ($query) {
                $query->where('dialer_onscript_recording_upload_logs.status', '<>', 'processing');
            })
            ->when($request->filled(['start_date', 'end_date']), function ($query) use ($startDate, $endDate) {
                $query->whereBetween('auditlog.timestamp', [
                    $startDate->startOfDay(),
                    $endDate->endOfDay(),
                ]);
            })
            ->orderBy('dialer_onscript_recording_upload_logs.id', 'DESC')
            ->get();

        $items->map(function ($item) {
            if (!empty($item->onscript_id)) {
                $item->link = sprintf('<a href="https://app.onscript.ai/ui/dialog_detail/%s" target="_blank" class="btn btn-primary btn-sm">View</a>',
                    urlencode($item->onscript_id),
                );
            }
        });

        $allow_list = [
            'id',
            'call_id',
            'onscript_id',
            'status',
            'time_stamp',
            'timestamp',
            'link',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Call ID', 'field' => 'call_id', 'displayFormat' => 'text',],
                ['label' => 'Call Date', 'field' => 'time_stamp', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Upload Date', 'field' => 'timestamp', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Status', 'field' => 'status', 'displayFormat' => 'text',],
                ['label' => 'OnScript Entry', 'field' => 'link', 'displayFormat' => 'text',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "OnScript Upload Logs.xlsx");
    }


    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerPettyCashEntry::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Petty Cash entry not found', 400);
            }
        } else {
            $item = new DialerPettyCashEntry();
            $item->agent_id = $request->user()->id;
        }

        $validator = Validator::make($request->all(), [
            'date' => 'bail|required|date',
            'petty_cash_location_id' => "bail|required|integer|exists:dialer_petty_cash_locations,id",
            'petty_cash_vendor_id' => "bail|required|integer|exists:dialer_petty_cash_vendors,id",
            'petty_cash_reason_id' => "bail|required|integer|exists:dialer_petty_cash_reasons,id",
            'petty_cash_note_id' => "bail|nullable|integer|exists:dialer_petty_cash_notes,id",
            'absAmount' => "bail|required|min:0.01|regex:/^\d+(\.\d{1,2})?$/",
            'type' => 'bail|string|required|in:in,out',
            'isArchived' => 'bail|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $amount = abs($request->input('absAmount'));
        if ($request->input('type') == 'out') {
            $amount *= -1;
        }

        DB::transaction(function () use ($item, $request, $amount) {
            $item->date = $request->input('date');
            $item->amount = $amount;
            $item->petty_cash_location_id = $request->input('petty_cash_location_id');
            $item->petty_cash_vendor_id = $request->input('petty_cash_vendor_id');
            $item->petty_cash_reason_id = $request->input('petty_cash_reason_id');
            $item->petty_cash_note_id = $request->input('petty_cash_note_id');
            $item->type = $request->input('type');
            if ($item->exists) {
                if ($request->boolean('isArchived')) {
                    $item->delete();
                } else {
                    $item->restore();
                }
            }
            $item->save();
        });

        return response([]);
    }
}
