<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerExternalCampaign;
use App\Models\DialerPettyCashLocation;
use App\Responses\ErrorResponse;
use App\Rules\SlugRule;
use App\Validators\ApiJsonValidator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DialerExternalCampaignController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        ApiJsonValidator::validate($request->all(), [
            'search' => 'bail|string|nullable',
            'include_archived' => 'bail|string',
        ]);

        AuditLog::createFromRequest($request, 'DIALER-EXTERNAL-CAMPAIGN:LIST', [
            'search' => $request->input('search'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerExternalCampaign::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('name', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->orderBy('name')
            ->get();

        $items->map(function ($item) {
            $item->campaign_name = $item->campaign?->name;
        });

        $allow_list = [
            'id',
            'name',
            'campaign_name',
            'isActive',
            'isArchived',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Name', 'field' => 'name',],
                ['label' => 'Linked Campaign', 'field' => 'campaign_name',],
                ['label' => 'Active', 'field' => 'isActive', 'displayFormat' => 'boolean_icon',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "External Campaigns.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerExternalCampaign::withTrashed()->find($item_id);
        }

        if (!isset($item)) {
            $item = new DialerExternalCampaign();
        }

        ApiJsonValidator::validate($request->all(), [
            'id' => 'bail|required|integer',
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                $item->exists ? Rule::unique('dialer_external_campaigns')->ignore($item) : Rule::unique('dialer_external_campaigns'),
            ],
            'campaign_id' => 'bail|required|exists:dialer_products,id',
        ]);

        DB::transaction(function () use ($item, $request) {
            $item->id = $request->input('id');
            $item->name = $request->input('name');
            $item->campaign_id = $request->input('campaign_id');
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
