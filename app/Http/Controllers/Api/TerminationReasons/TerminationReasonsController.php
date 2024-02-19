<?php

namespace App\Http\Controllers\Api\TerminationReasons;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAgentTerminationReason;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TerminationReasonsController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        AuditLog::createFromRequest($request, 'DIALER-TERMINATION-REASONS:LIST');

        $items = DialerAgentTerminationReason::query()
            ->orderBy('reason')
            ->get();

        $allow_list = [
            'id',
            'reason',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Reason', 'field' => 'reason',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Termination Reasons.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerAgentTerminationReason::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Termination reason not found', 400);
            }
        } else {
            $item = new DialerAgentTerminationReason();
        }

        $validator = Validator::make($request->all(), [
            'reason' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_agent_termination_reasons')->ignore($item) : Rule::unique('dialer_agent_termination_reasons'),
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($item, $request) {
            $item->reason = $request->input('reason');
            $item->save();
        });

        return response([]);
    }
}
