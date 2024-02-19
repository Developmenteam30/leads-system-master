<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ActionButtonHelper;
use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DialerAgent;
use App\Models\User;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditLogController extends BaseController
{
    /**
     * Load a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|bail|date',
            'end_date' => 'required|bail|date',
            'search' => 'nullable|string',
            'agent_ids' => 'bail|string|nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        AuditLog::createFromRequest($request, 'ADMIN:AUDIT-LOG', [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
            'agent_ids' => $request->input('agent_ids'),
        ]);

        $items = AuditLog::query()
            ->join('dialer_agents', 'dialer_agents.id', 'auditlog.agent_id')
            ->select([
                'logId', 'logId as id', 'timestamp', 'ipaddress', 'dialer_agents.agent_name', 'action', 'notes',
            ])
            ->whereBetween('timestamp', [
                $startDate,
                $endDate,
            ])
            ->when($request->filled('agent_ids'), function ($query) use ($request) {
                return $query->whereIn('dialer_agents.id', explode(',', $request->input('agent_ids')));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('action', 'LIKE', '%'.$request->input('search').'%');
            })
            ->orderBy('logId')
            ->get();

        $items->map(function ($item) use ($request) {
            $item->actions = ActionButtonHelper::view($item);

            return $item;
        });

        $allow_list = [
            'id',
            'logId',
            'timestamp',
            'ipaddress',
            'agent_name',
            'action',
            'actions',
        ];
        $datatable = [
            'columns' => array_merge([
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'logId', 'field' => 'logId', 'fixed' => true],
                ['label' => 'Date/Time', 'field' => 'timestamp', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Ip Address', 'field' => 'ipaddress', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Agent Name', 'field' => 'agent_name', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Action', 'field' => 'action', 'displayFormat' => 'text', 'fixed' => true],
                ['label' => 'Actions', 'field' => 'actions',],
            ]),
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Audit Log {$startDate->format('Ymd')} - {$endDate->format('Ymd')}.xlsx");
    }
}
