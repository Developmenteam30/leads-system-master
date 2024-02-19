<?php

namespace App\Http\Controllers\Api\Campaigns;

use App\Helpers\DataTableFields;
use App\Models\DialerAccessArea;
use App\Models\DialerCampaignDefault;
use App\Responses\ErrorResponse;
use App\Rules\NumericEmptyOrWithDecimal;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CampaignDefaultsController extends BaseController
{
    /**
     * Load campaign defaults
     *
     * @param  Request  $request
     * @return array
     */
    public function index(Request $request, $campaign_id): array
    {
        $items = DialerCampaignDefault::query()
            ->join('companies', 'companies.idCompany', 'dialer_campaign_defaults.company_id')
            ->select('dialer_campaign_defaults.*')
            ->where('campaign_id', $campaign_id)
            ->orderBy('companies.name')
            ->get();

        $items->map(function ($item) {
            $item->company_name = $item->company->name ?? '';
            $item->payment_type_name = $item->paymentType->name ?? '';
            $item->payable_rate_formatted = !empty($item->payable_rate) ? '$'.number_format($item->payable_rate, 2) : '';
            $item->billable_rate_formatted = !empty($item->billable_rate) ? '$'.number_format($item->billable_rate, 2) : '';
            $item->bonus_rate_formatted = !empty($item->bonus_rate) ? '$'.number_format($item->bonus_rate, 2) : '';

            return $item;
        });

        $allow_list = array_merge([
            'id',
            'campaign_id',
            'payable_rate',
            'payable_rate_formatted',
            'bonus_rate',
            'bonus_rate_formatted',
            'product_id',
            'company_id',
            'company_name',
            'payment_type_id',
            'payment_type_name',
        ], $request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES") ? [
            'billable_rate',
            'billable_rate_formatted',
        ] : []);

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Call Center', 'field' => 'company_name',],
                ['label' => 'Payment Type', 'field' => 'payment_type_name'],
                ['label' => 'Billable Rate (US$)', 'field' => 'billable_rate_formatted'],
                ['label' => 'Payable Rate (US$)', 'field' => 'payable_rate_formatted'],
                ['label' => 'Bonus Rate (US$)', 'field' => 'bonus_rate_formatted'],
            ],
            'rows' => $items,
        ];

        return DataTableFields::getByAllowList($datatable, $allow_list);
    }

    /**
     * Add/update a campaign default
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerCampaignDefault::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Campaign default not found', 400);
            }
        } else {
            $item = new DialerCampaignDefault();
        }

        $validator = Validator::make($request->all(), [
            'campaign_id' => 'bail|required|exists:dialer_products,id',
            'company_id' => 'bail|required|exists:companies,idCompany',
            'payment_type_id' => 'bail|required|exists:dialer_payment_types,id',
            'billable_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'payable_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
            'bonus_rate' => ['bail', 'nullable', new NumericEmptyOrWithDecimal, 'numeric', 'between:0,9999.99'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        try {
            DB::transaction(function () use ($item, $request) {
                $item->campaign_id = $request->input('campaign_id');
                $item->company_id = $request->input('company_id');
                $item->payment_type_id = $request->input('payment_type_id');
                if ($request->user()->hasAccessToArea("ACCESS_AREA_BILLABLE_RATES")) {
                    $item->billable_rate = $request->input('billable_rate');
                }
                $item->payable_rate = $request->input('payable_rate');
                $item->bonus_rate = $request->input('bonus_rate');
                $item->save();
            });
        } catch (QueryException $e) {
            if (!empty($e->errorInfo[0]) && '23000' === $e->errorInfo[0]) {
                return ErrorResponse::json("A default already exists for this company. Please edit the existing entry rather than adding a new one.", 400);
            } else {
                return ErrorResponse::json("Database Error: {$e->getMessage()}", 400);
            }
        }

        return response([]);
    }
}
