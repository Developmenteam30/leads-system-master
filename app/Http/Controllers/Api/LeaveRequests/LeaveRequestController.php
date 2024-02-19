<?php

namespace App\Http\Controllers\Api\LeaveRequests;

use App\Helpers\ActionButtonHelper;
use App\Helpers\DataTableFields;
use App\Jobs\LeaveRequestStatusEmailJob;
use App\Jobs\LeaveRequestSubmittedEmailJob;
use App\Models\AuditLog;
use App\Models\DialerLeaveRequest;
use App\Models\DialerLeaveRequestStatus;
use App\Models\DialerLeaveRequestType;
use App\Responses\ErrorResponse;
use App\Validators\ApiJsonValidator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\Rule;

class LeaveRequestController extends BaseController
{
    /**
     * Get leave requests for an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request, $agent_id = null)
    {
        ApiJsonValidator::validate($request->all(), [
            'start_date' => 'bail|nullable|date',
            'end_time' => 'bail|nullable|date',
            'search' => 'bail|string|nullable',
            'leave_request_status_ids' => 'nullable|string',
            'actions' => 'bail|nullable|boolean',
        ]);

        if (!$request->user()->hasAccessToArea("ACCESS_AREA_ADD_EDIT_AGENTS") && !$request->user()->hasAccessToArea("ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS")) {
            $agent_id = $request->user()->id;
        }

        AuditLog::createFromRequest($request, 'DIALER-LEAVE-REQUEST:LIST', [
            'agent_id' => $agent_id,
            'start_date' => $request->input('start_date'),
            'end_time' => $request->input('end_time'),
            'search' => $request->input('search'),
            'leave_request_status_ids' => $request->input('leave_request_status_ids'),
        ]);

        $startDate = CarbonImmutable::parse($request->input('start_date'))->setTime(0, 0);
        $endDate = CarbonImmutable::parse($request->input('end_time'))->setTime(23, 59, 59);

        if ($agent_id) {
            $query = DialerLeaveRequest::where('agent_id', $agent_id);
        } else {
            $query = DialerLeaveRequest::query();
        }

        $items = $query
            ->with([
                'agent',
                'reviewer',
                'type',
            ])
            ->when($request->filled(['start_date', 'end_time']), function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [
                    $startDate,
                    $endDate,
                ]);
            })
            ->when($request->filled('leave_request_status_ids'), function ($query) use ($request) {
                return $query->whereIn('leave_request_status_id', explode(',', $request->input('leave_request_status_ids')));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('agent', function (Builder $query) use ($request) {
                    $query->where('agent_name', 'LIKE', '%'.$request->input('search').'%');
                });
            })
            ->get();

        $items->transform(function ($item) use ($request) {
            $item->agent_name = $item->agent?->agent_name;
            $item->type_name = $item->type?->name;
            $item->status_name = $item->status?->name;
            $item->reviewer_name = $item->reviewer?->agent_name;

            if ($request->boolean('actions')) {
                // $item->actions = ActionButtonHelper::actionbuttons([
                //     [ 'type' => 'view', 'id' => $item->id ],
                //     [ 'type' => 'edit', 'id' => $item->id ],
                //     [ 'type' => 'delete', 'id' => $item->id ],
                //     [ 'type' => 'upload', 'id' => $item->id ],
                // ]);
                $item->actions = ActionButtonHelper::view($item);
                $item->actions .= ActionButtonHelper::upload($item);
                if ($request->user()->hasAccessToArea("ACCESS_AREA_EDIT_LEAVE_REQUESTS")) {
                    $item->actions .= '<div style="height:10px;">&nbsp</div>';
                    $item->actions .= ActionButtonHelper::edit($item);
                    $item->actions .= ActionButtonHelper::delete($item);
                }
            }

            return $item;
        });

        $allow_list = array_merge([
            'id',
            'agent_name',
            'reviewer_name',
            'formattedStartDate',
            'formattedStartTime',
            'formattedEndDate',
            'formattedEndTime',
            'status_name',
            'type_name',
            'actions',
        ], $request->boolean('actions') ? ['actions'] : []);

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Agent Name', 'field' => 'agent_name', 'displayFormat' => 'text',],
                ['label' => 'Type', 'field' => 'type_name', 'displayFormat' => 'text',],
                ['label' => 'Start Date', 'field' => 'formattedStartDate', 'displayFormat' => DataTableFields::DATE_YYYYMMDD],
                ['label' => 'Start Time (Belize)', 'field' => 'formattedStartTime', 'displayFormat' => DataTableFields::TIME_12HOUR],
                ['label' => 'End Date', 'field' => 'formattedEndDate', 'displayFormat' => DataTableFields::DATE_YYYYMMDD],
                ['label' => 'End Time (Belize)', 'field' => 'formattedEndTime', 'displayFormat' => DataTableFields::TIME_12HOUR],
                ['label' => 'Status', 'field' => 'status_name', 'displayFormat' => 'text',],
                ['label' => 'Reviewed By', 'field' => 'reviewer_name', 'displayFormat' => 'text',],
                ['label' => 'Actions', 'field' => 'actions',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Leave Requests.xlsx");
    }

    /**
     * Add / update a leave request
     *
     * @param  \Illuminate\Http\Request  $request
     * @throws \Exception
     */
    public function update(Request $request, $item_id = null)
    {
        if (!$request->user()->hasAccessToArea("ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS")) {
            $request->merge(['agent_id' => $request->user()->id]);
        }

        ApiJsonValidator::validate($request->all(), [
            'agent_id' => 'bail|required|exists:dialer_agents,id',
            'leave_request_type_id' => 'bail|required|exists:dialer_leave_request_types,id',
            'formattedStartDate' => 'bail|required|date',
            'formattedStartTime' => [
                'bail',
                'nullable',
                'string',
                'regex:/^\d{2}:\d{2} [AaPp][Mm]$/',
            ],
            'formattedEndDate' => 'bail|required|date',
            'formattedEndTime' => [
                'bail',
                'nullable',
                'string',
                'regex:/^\d{2}:\d{2} [AaPp][Mm]$/',
            ],
            'notes' => [
                'bail',
                'nullable',
                'string',
                'max:65535',
            ],
            'leave_request_status_id' => [
                Rule::excludeIf(fn() => !$request->user()->hasAccessToArea("ACCESS_AREA_EDIT_LEAVE_REQUESTS")),
                'required',
                'bail',
                'exists:dialer_leave_request_statuses,id',
            ],
        ]);

        if (!empty($item_id)) {
            $item = DialerLeaveRequest::find($item_id);
            if (!$item) {
                return ErrorResponse::json('Leave request not found', 400);
            }
        } else {
            $item = new DialerLeaveRequest();
            $item->leave_request_status_id = DialerLeaveRequestStatus::PENDING;
        }

        $originalStatus = $item->leave_request_status_id;

        if ($request->filled('formattedStartTime')) {
            $start_time = CarbonImmutable::parse($request->input('formattedStartDate').$request->input('formattedStartTime'), new \DateTimeZone(config('settings.timezone.belize')));
        } else {
            $start_time = CarbonImmutable::parse($request->input('formattedStartDate').' 00:00:00', new \DateTimeZone(config('settings.timezone.belize')));
        }

        if ($request->filled('formattedEndTime')) {
            $end_time = CarbonImmutable::parse($request->input('formattedEndDate').$request->input('formattedEndTime'), new \DateTimeZone(config('settings.timezone.belize')));
        } else {
            $end_time = CarbonImmutable::parse($request->input('formattedEndDate').' 00:00:00', new \DateTimeZone(config('settings.timezone.belize')));
        }

        $item->leave_request_type_id = $request->input('leave_request_type_id');
        $item->agent_id = $request->input('agent_id');
        $item->start_time = $start_time->timezone('UTC');
        $item->end_time = $end_time->timezone('UTC');

        if (!$item->exists) {
            switch ($item->leave_request_type_id) {
                case DialerLeaveRequestType::SICK:
                    $diff = ceil($item->start_time->startOfDay()->floatDiffInDays($item->end_time->endOfDay()));

                    if ($diff > $item->agent->sickRemaining) {
                        return ErrorResponse::json('This request exceeds the number of sick days you have available.', 400);
                    }

                    break;

                case DialerLeaveRequestType::PTO:
                    $diff = $item->start_time->diffInHours($item->end_time);

                    if ($diff > $item->agent->ptoRemaining) {
                        return ErrorResponse::json('This request exceeds the number of PTO hours you have available.', 400);
                    }

                    break;
            }
        }

        $item->notes = $request->input('notes');
        if ($request->user()->hasAccessToArea("ACCESS_AREA_MENU_PEOPLE_LEAVE_REQUESTS")) {
            $item->leave_request_status_id = $request->input('leave_request_status_id');
            if ($item->leave_request_status_id !== $originalStatus) {
                $item->reviewer_agent_id = $request->user()->id ?? null;
            }
        }
        $item->save();

        if ($item->wasRecentlyCreated) {
            LeaveRequestSubmittedEmailJob::dispatch(
                request: $item,
            );
        } elseif ($item->leave_request_status_id !== $originalStatus) {
            LeaveRequestStatusEmailJob::dispatch(
                request: $item,
            );
        }

        return response([]);
    }

    /**
     * Delete a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function delete(Request $request, $item_id = null)
    {
        ApiJsonValidator::validate($request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_leave_requests,id',
        ]);

        $item = DialerLeaveRequest::withTrashed()->find($item_id);
        $item->delete();

        return response([]);
    }
}
