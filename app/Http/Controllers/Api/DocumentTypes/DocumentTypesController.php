<?php

namespace App\Http\Controllers\Api\DocumentTypes;

use App\Helpers\DataTableFields;
use App\Models\AuditLog;
use App\Models\DialerDocumentType;
use App\Responses\ErrorResponse;
use App\Rules\ClassExists;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentTypesController extends BaseController
{
    /**
     * Load an agent
     *
     * @param  \Illuminate\Http\Request  $request
     */
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $agent_id = null)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'bail|string|nullable',
            'include_archived' => 'bail|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        AuditLog::createFromRequest($request, 'DIALER-DOCUMENT-TYPES:LIST', [
            'search' => $request->input('search'),
            'include_archived' => $request->input('include_archived'),
        ]);

        $items = DialerDocumentType::query()
            ->select([
                'id',
                'name',
                'documentable_type',
                'deleted_at',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('name', 'LIKE', '%'.$request->input('search').'%');
            })
            ->when($request->boolean('include_archived'), function ($query) use ($request) {
                $query->withTrashed();
            })
            ->orderBy('name')
            ->get();

        $allow_list = [
            'id',
            'name',
            'isActive',
            'isArchived',
            'documentable_type',
        ];

        $datatable = [
            'columns' => [
                ['label' => 'ID', 'field' => 'id', 'fixed' => true],
                ['label' => 'Name', 'field' => 'name',],
                ['label' => 'Database Class', 'field' => 'documentable_type',],
                ['label' => 'Active', 'field' => 'isActive', 'displayFormat' => 'boolean_icon',],
            ],
            'rows' => $items,
        ];

        return DataTableFields::displayOrExport($datatable, $allow_list, $request, "Document Types.xlsx");
    }

    /**
     * Add an item
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $item_id = null)
    {
        if (!empty($item_id)) {
            $item = DialerDocumentType::withTrashed()->find($item_id);
            if (!$item) {
                return ErrorResponse::json('Document type not found', 400);
            }
        } else {
            $item = new DialerDocumentType();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                !empty($item) ? Rule::unique('dialer_document_types')->where(fn(Builder $query) => $query->where('documentable_type', $request->input('documentable_type')))->ignore($item)
                    : Rule::unique('dialer_document_types')->where(fn(Builder $query) => $query->where('documentable_type', $request->input('documentable_type'))),
            ],
            'documentable_type' => ['bail', 'string', new ClassExists()],
            'isArchived' => 'bail|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        try {
            DB::beginTransaction();

            $item->name = $request->input('name');
            $item->documentable_type = $request->input('documentable_type');
            if ($item->exists) {
                if ($request->boolean('isArchived')) {
                    if ($item->entries->count()) {
                        return ErrorResponse::json("This document type is in use by {$item->entries->count()} ".Str::plural('document', $item->entries->count()).".", 400);
                    }
                    $item->delete();
                } else {
                    $item->restore();
                }
            }
            $item->save();

            DB::commit();

            return $item;

        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorResponse::json('DB error: '.$e->getMessage(), 400);
        }
    }

    /**
     * Delete a record
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function delete(Request $request, $item_id = null)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'id' => 'required|bail|exists:dialer_document_Types,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        $item = DialerDocumentType::withTrashed()->find($item_id);
        if ($item->entries->count()) {
            return ErrorResponse::json("This document type is in use by {$item->entries->count()} ".Str::plural('document', $item->entries->count()).".", 400);
        }

        $item->delete();

        return response([]);
    }
}
