<?php

namespace App\Http\Controllers\Api\Terminations;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAgentEffectiveDate;
use App\Models\DialerAgentTermination;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManageController extends BaseController
{

    /**
     * Load agents termination list
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        AuditLog::createFromRequest($request, 'DIALER-TERMINATION-LOG:LIST');

        $items = DialerAgentTermination::query()
            ->with([
                'agent',
                'reason',
                'nominator',
            ])
            ->orderBy('created_at')
            ->get();

        $items->map(function ($item) use ($request) {
            $item->agent_name = $item->agent->agent_name;
            $item->nominator_name = $item->nominator->agent_name;
            $item->campaign_name = $item->agent->mostRecentEffectiveDate?->product?->name ?? null;
            $item->manager_name = $item->agent->team?->manager?->agent_name ?? null;

            $item->actions .= '<button title="View" class="view-btn btn btn-outline-primary btn-floating btn-sm" data-mdb-number="'.$item->id.'"><i class="fa fa-eye"></i></button>';
            if ($request->user()->hasAccessToArea("ACCESS_AREA_EDIT_TERMINATIONS") || $item->nominator_id === $request->user()->id) {
                $item->actions .= '<button title="Edit" class="edit-btn btn btn-outline-primary btn-floating btn-sm ms-2" data-mdb-number="'.$item->id.'"><i class="fa fa-edit"></i></button>';
            }
            if ($request->user()->hasAccessToArea("ACCESS_AREA_EDIT_TERMINATIONS")) {
                $item->actions .= '<button title="Delete" class="delete-btn btn btn-outline-primary btn-floating btn-sm ms-2" data-mdb-number="'.$item->id.'"><i class="fa fa-trash"></i></button>';
            }

            return $item;
        });

        $allow_list = [
            'id',
            'agent_name',
            'campaign_name',
            'manager_name',
            'sdr_report_date',
            'pip_issue_date',
            'term_approve_date',
            'nominator_name',
            'notes',
            'reason_id',
            'actions',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true, 'displayFormat' => 'text',],
                ['label' => 'Agent', 'field' => 'agent_name', 'displayFormat' => 'text',],
                ['label' => 'Campaign', 'field' => 'campaign_name', 'displayFormat' => 'text',],
                ['label' => 'Manager', 'field' => 'manager_name', 'displayFormat' => 'text',],
                ['label' => 'SDR Report Date', 'field' => 'sdr_report_date', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'PIP Issue Date', 'field' => 'pip_issue_date', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Term Approve Date', 'field' => 'term_approve_date', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Nominator', 'field' => 'nominator_name', 'displayFormat' => 'text',],
                ['label' => 'Notes', 'field' => 'notes', 'displayFormat' => 'text',],
                ['label' => 'Actions', 'field' => 'actions', 'show' => $request->isNotFilled('export'),],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Termination Log.xlsx");
    }

    /**
     * Add or edit a record
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $item_id
     */
    public function update(Request $request, $item_id = null)
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'bail|required|exists:dialer_agents,id',
            'sdr_report_date' => 'bail|required|date',
            'pip_issue_date' => 'bail|required|date',
            'term_approve_date' => 'bail|date',
            'reason_id' => 'bail|required|exists:dialer_agent_termination_reasons,id',
            'notes' => 'bail|string|max:65535',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        if (!empty($item_id)) {
            $item = DialerAgentTermination::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Termination not found', 400);
            }

            if (!$request->user()->hasAccessToArea("ACCESS_AREA_EDIT_TERMINATIONS") && $item->nominator_id !== $request->user()->id) {
                return ErrorResponse::json('You do not have access to edit this termination', 400);
            }
        } else {
            $item = new DialerAgentTermination();
            $item->nominator_id = $request->user()->id;
        }

        DB::transaction(function () use ($item, $request) {
            $item->agent_id = $request->input('agent_id');
            $item->sdr_report_date = $request->input('sdr_report_date');
            $item->pip_issue_date = $request->input('pip_issue_date');
            $item->term_approve_date = $request->input('term_approve_date');
            $item->nominator_id = $request->user()->id;
            $item->reason_id = $request->input('reason_id');
            $item->notes = $request->input('notes');
            $item->save();

            if ($request->filled('term_approve_date')) {
                $dates = DialerAgentEffectiveDate::query()
                    ->where('agent_id', $item->agent_id)
                    ->whereNull('end_date')
                    ->get();

                $dates->each(function ($date) use ($request) {
                    $date->end_date = $request->input('term_approve_date');
                    $date->termination_reason_id = $request->input('reason_id');
                    $date->save();
                });
            }
        });

        return response([]);
    }

    /**
     * Delete a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function delete(Request $request, $item_id = null)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_agent_terminations,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerAgentTermination::withTrashed()->find($item_id);
        $item->delete();

        return response([]);
    }
}

