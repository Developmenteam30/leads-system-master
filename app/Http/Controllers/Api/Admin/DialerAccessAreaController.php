<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAccessArea;
use App\Responses\ErrorResponse;
use App\Rules\SlugRule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DialerAccessAreaController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        AuditLog::createFromRequest($request, 'DIALER-ACCESS-AREAS:LIST');

        $items = DialerAccessArea::query()
            ->orderBy('slug')
            ->get();

        $allow_list = [
            'id',
            'name',
            'description',
            'slug',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Slug', 'field' => 'slug',],
                ['label' => 'Name', 'field' => 'name',],
                ['label' => 'Description', 'field' => 'description',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Dialer Access Areas.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerAccessArea::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Access Area not found', 400);
            }
        } else {
            $item = new DialerAccessArea();
        }

        $validator = Validator::make($request->all(), [
            'slug' => [
                'bail',
                'required',
                'string',
                'max:255',
                new SlugRule,
                !empty($item) ? Rule::unique('dialer_access_areas')->ignore($item) : Rule::unique('dialer_access_areas'),
            ],
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_access_areas')->ignore($item) : Rule::unique('dialer_access_areas'),
            ],
            'description' => 'bail|nullable|string|max:255',
        ], [
            'slug.regex' => 'The slug can only contain A-Z and underscores, and must start and end with a character.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($item, $request) {
            $slug = strtoupper($request->input('slug'));
            $item->name = $request->input('name');
            $item->description = $request->input('description');
            $item->slug = (!str_starts_with($slug, 'ACCESS_AREA_',) ? 'ACCESS_AREA_' : '').$slug;
            $item->save();
        });

        return response([]);
    }
}
