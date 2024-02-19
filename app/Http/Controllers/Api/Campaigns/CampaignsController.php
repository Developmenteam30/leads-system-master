<?php

namespace App\Http\Controllers\Api\Campaigns;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerProduct;
use App\Responses\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CampaignsController extends BaseController
{
    /**
     * Load a campaign
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        AuditLog::createFromRequest($request, 'DIALER-CAMPAIGNS:LIST');

        $items = DialerProduct::query()
            ->orderBy('name')
            ->get();

        $allow_list = [
            'id',
            'name',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Campaign Name', 'field' => 'name',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Campaigns.xlsx");
    }

    /**
     * Add/update a campaign
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerProduct::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Role not found', 400);
            }
        } else {
            $item = new DialerProduct();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_products')->ignore($item) : Rule::unique('dialer_products'),
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
