<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\ActionButtonHelper;
use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerAgent;
use App\Models\DialerDocument;
use App\Models\DialerDocumentType;
use App\Models\DialerLeaveRequest;
use App\Validators\ApiJsonValidator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class DocumentsController extends BaseController
{
    /**
     * Display a listing of the documents for all users.
     *
     * @param  Request  $request
     */
    public function index(Request $request)
    {

        ApiJsonValidator::validate(
            $request->all(), [
                'documentable_type' => 'bail|string|nullable',
                'documentable_id' => 'bail|string|nullable',
                'document_type_ids' => 'bail|string|nullable',
                'agent_search' => 'bail|string|nullable',
                'title_search' => 'bail|string|nullable',
                'include_archived' => 'bail|string',
            ]
        );

        AuditLog::createFromRequest($request, 'DIALER-DOCUMENTS:LIST', [
            'document_type_ids' => $request->input('document_type_ids'),
            'agent_search' => $request->input('agent_search'),
            'title_search' => $request->input('title_search'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerDocument::query()
            ->when($request->filled('agent_search'), function ($query) use ($request) {
                $query->whereHasMorph('documentable', [DialerAgent::class], function ($query) use ($request) {
                    $query->where('agent_name', 'LIKE', '%'.$request->input('agent_search').'%');
                });
            })
            ->when($request->filled('title_search'), function ($query) use ($request) {
                $query->where('title', 'LIKE', '%'.$request->input('title_search').'%');
            })
            ->when($request->filled('documentable_id') && $request->filled('documentable_type'), function ($query) use ($request) {
                $query->where('documentable_type', DialerDocumentType::getDocumentableType($request->input('documentable_type')))
                    ->where('documentable_id', $request->input('documentable_id'));
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->when($request->filled('document_type_ids'), function ($query) use ($request) {
                return $query->whereIn('document_type_id', explode(',', $request->input('document_type_ids')));
            })
            ->orderByDesc('created_at')
            ->get();

        $items->transform(function ($item) {
            $item->document_type_name = $item->documentType?->name;

            switch ($item->documentable_type) {
                case DialerAgent::class:
                    $item->upload_type = 'Agent Profile';
                    $item->agent_name = $item->documentable?->agent_name;
                    break;

                case DialerLeaveRequest::class:
                    $item->upload_type = 'Leave Request';
                    $item->agent_name = $item->documentable?->agent->agent_name;
                    break;
            }

            $item->download_url = sprintf('<a href="%s">%s</a>',
                $item->getTemporaryDownloadUrl(now()->addMinutes(30)),
                $item->title,
            );

            $item->actions .= ActionButtonHelper::deleteOrRestore($item);

            return $item;
        });

        $allow_list = [
            'actions',
            'agent_name',
            'created_at',
            'document_type_name',
            'download_url',
            'id',
            'upload_type',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id'],
                ['label' => 'Agent', 'field' => 'agent_name'],
                ['label' => 'Upload Type', 'field' => 'upload_type'],
                ['label' => 'Document Type', 'field' => 'document_type_name'],
                ['label' => 'Title', 'field' => 'download_url'],
                ['label' => 'Upload Date', 'field' => 'created_at', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
                ['label' => 'Actions', 'field' => 'actions',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Agent Documents.xlsx");
    }

    /**
     * Display a listing of the documents for a specific user.
     *
     * @param  Request  $request
     * @param  int  $agent_id
     */
    public function agent(Request $request, $agent_id)
    {
        // Find the user or fail
        $agent_id = $request->user()->hasAccessToArea("ACCESS_AREA_MENU_PEOPLE_DOCUMENTS") ? $agent_id : $request->user()->id;
        $user = DialerAgent::findOrFail($agent_id);

        AuditLog::createFromRequest($request, 'DIALER-DOCUMENTS:LIST', [
            'agent_id' => $agent_id,
        ]);

        $documents = $user->documents->transform(function ($document) {

            $document->document_type_name = $document->documentType?->name;

            $document->download_url = sprintf('<a href="%s">%s</a>',
                $document->getTemporaryDownloadUrl(now()->addMinutes(30)),
                $document->title,
            );

            return $document;
        });

        $allow_list = [
            'id',
            'download_url',
            'created_at',
            'document_type_name',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id'],
                ['label' => 'Type', 'field' => 'document_type_name'],
                ['label' => 'Title', 'field' => 'download_url'],
                ['label' => 'Upload Date', 'field' => 'created_at', 'displayFormat' => DataTableFields::DATE_YYYYMMDD,],
            ],
            'rows' => $documents,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Agent Documents.xlsx");
    }

    /**
     * Delete a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function delete(Request $request, $item_id = null)
    {
        ApiJsonValidator::validate(
            $request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_documents,id',
        ]);

        DialerDocument::withTrashed()->find($item_id)->delete();

        return response([]);
    }

    /**
     * Restore a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function restore(Request $request, $item_id = null)
    {
        ApiJsonValidator::validate(
            $request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_documents,id',
        ]);

        DialerDocument::withTrashed()->find($item_id)->restore();

        return response([]);
    }
}
