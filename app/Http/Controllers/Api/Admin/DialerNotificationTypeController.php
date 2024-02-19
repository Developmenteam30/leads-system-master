<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerNotificationType;
use App\Responses\ErrorResponse;
use App\Rules\SlugRule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DialerNotificationTypeController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        AuditLog::createFromRequest($request, 'DIALER-NOTIFICATION-TYPE:LIST');

        $items = DialerNotificationType::query()
            ->orderBy('slug')
            ->get();

        $allow_list = [
            'id',
            'slug',
            'description',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Slug', 'field' => 'slug',],
                ['label' => 'Description', 'field' => 'description',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Dialer Notification Types.xlsx");
    }

    /**
     * Add an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerNotificationType::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Notification Type not found', 400);
            }
        } else {
            $item = new DialerNotificationType();
        }

        $validator = Validator::make($request->all(), [
            'slug' => [
                'bail',
                'required',
                'string',
                'max:255',
                new SlugRule,
                !empty($item) ? Rule::unique('dialer_notification_types')->ignore($item) : Rule::unique('dialer_notification_types'),
            ],
            'description' => 'bail|required|string|max:255',
        ], [
            'slug.regex' => 'The slug can only contain A-Z and underscores, and must start and end with a character.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        DB::transaction(function () use ($item, $request) {
            $slug = strtoupper($request->input('slug'));
            $item->description = $request->input('description');
            $item->slug = (!str_starts_with($slug, 'NOTIFICATION_TYPE_',) ? 'NOTIFICATION_TYPE_' : '').$slug;
            $item->save();
        });

        return response([]);
    }
}
