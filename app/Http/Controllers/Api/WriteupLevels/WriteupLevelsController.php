<?php

namespace App\Http\Controllers\Api\WriteupLevels;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAgentWriteupLevel;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WriteupLevelsController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        AuditLog::createFromRequest($request, 'DIALER-WRITEUP-LEVELS:LIST');

        $items = DialerAgentWriteupLevel::query()
            ->orderBy('name')
            ->get();

        $allow_list = [
            'id',
            'name',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Level', 'field' => 'name',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Write-Up Levels.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerAgentWriteupLevel::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Write-up level not found', 400);
            }
        } else {
            $item = new DialerAgentWriteupLevel();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_agent_writeup_levels')->ignore($item) : Rule::unique('dialer_agent_writeup_levels'),
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($item, $request) {
            $item->name = $request->input('name');
            $item->save();
        });

        return response([]);
    }
}
