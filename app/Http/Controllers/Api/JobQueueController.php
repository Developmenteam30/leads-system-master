<?php

namespace App\Http\Controllers\Api;

use App\Jobs\GenerateCallDetailUploadJob;
use App\Jobs\GeneratePerformanceReportUploadJob;
use App\Jobs\ProcessAgentPerformanceFile;
use App\Jobs\ProcessAgentPerformanceImport;
use App\Jobs\ProcessCallDetailLogFile;
use App\Jobs\ProcessCallLogAPIJob;
use App\Jobs\ProcessRetreaverFileJob;
use App\Jobs\UploadOnScriptRecordingsJob;
use App\Jobs\UploadSharepointFileJob;
use App\Models\AuditLog;
use App\Responses\ErrorResponse;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JobQueueController extends BaseController
{
    /**
     * Load a report
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request)
    {
        $rows = AuditLog::query()
            ->leftJoin('dialer_agents', 'dialer_agents.id', 'auditlog.agent_id')
            ->select([
                'auditlog.logId',
                'auditlog.timestamp',
                'auditlog.userId',
                'auditlog.action',
                'auditlog.notes',
                'dialer_agents.id',
                'dialer_agents.agent_name',
            ])
            ->where('auditlog.action', 'LIKE', 'UPLOAD:%')
            ->orderBy('auditlog.logId', 'DESC')
            ->limit(250)
            ->get();

        $rows->each(function ($row) {
            if (!empty($row->notes)) {
                $row->timestamp = Carbon::parse($row->timestamp)->setTimezone(config('settings.timezone.local'))->format('Y-m-d H:i:s');
                $row->notes = json_decode($row->notes);
                $row->file = !empty($row->notes->file) ? basename($row->notes->file) : null;
                $row->file_date = $row->notes->file_date ?? null;
                $row->success = $row->notes->queueDetails->success ?? null;
                $row->message = $row->notes->queueDetails->message ?? null;
                $row->retry = sprintf('<button class="retry-btn btn btn-outline-primary btn-floating btn-sm" data-log-id="%s"><i class="fa fa-sync"></i></button>',
                    $row->logId
                );
            }
        });

        return $rows;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function retry(Request $request, $logId)
    {
        $validator = Validator::make($request->route()->parameters(), [
            'logId' => 'required|bail|exists:auditlog',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return ErrorResponse::json($errors->first(), 400);
        }

        try {
            $oldLog = AuditLog::find($logId);
            $oldLog->notes = json_decode($oldLog->notes);

            DB::beginTransaction();

            unset($oldLog->notes->queueDetails);
            $log = AuditLog::createFromRequest($request, $oldLog->action, (array) $oldLog->notes);

            switch ($oldLog->action) {
                case 'UPLOAD:DIALER-AGENT-PERFORMANCE':
                    if (preg_match('/^API Import: /', $oldLog->notes->file)) {
                        if (empty($oldLog->notes->file_date)) {
                            return ErrorResponse::json('Error: Missing old job parameters.', 400);
                        }
                        DB::commit();
                        ProcessAgentPerformanceImport::dispatch(
                            date: Carbon::parse($oldLog->notes->file_date),
                            email: $request->user()->email,
                            logId: $log->logId,
                        );
                    } else {
                        if (empty($oldLog->notes->file) || empty($oldLog->notes->file_date)) {
                            return ErrorResponse::json('Error: Missing old job parameters.', 400);
                        }
                        DB::commit();
                        ProcessAgentPerformanceFile::dispatch(
                            file: $oldLog->notes->file,
                            date: $oldLog->notes->file_date,
                            email: $request->user()->email,
                            logId: $log->logId,
                        );
                    }
                    break;

                case 'UPLOAD:DIALER-CALL-DETAIL-LOG':
                    if (empty($oldLog->notes->file) || empty($oldLog->notes->file_date)) {
                        return ErrorResponse::json('Error: Missing old job parameters.', 400);
                    }
                    DB::commit();
                    ProcessCallDetailLogFile::dispatch(
                        file: $oldLog->notes->file,
                        date: $oldLog->notes->file_date,
                        email: $request->user()->email,
                        logId: $log->logId,
                    );
                    break;

                case 'UPLOAD:SHAREPOINT':
                    if (empty($oldLog->notes->siteId) || empty($oldLog->notes->itemId) || empty($oldLog->notes->inputFile) || empty($oldLog->notes->file)) {
                        return ErrorResponse::json('Error: Missing old job parameters.', 400);
                    }
                    DB::commit();
                    UploadSharepointFileJob::dispatch(
                        siteId: $oldLog->notes->siteId,
                        itemId: $oldLog->notes->itemId,
                        inputFile: $oldLog->notes->inputFile,
                        file: $oldLog->notes->file,
                    );
                    break;

                case GenerateCallDetailUploadJob::ACTION_NAME:
                    if (empty($oldLog->notes->file_date)) {
                        return ErrorResponse::json('Error: Missing old job parameters.', 400);
                    }
                    DB::commit();
                    GenerateCallDetailUploadJob::dispatch(
                        date: Carbon::parse($oldLog->notes->file_date),
                    );
                    break;

                case GeneratePerformanceReportUploadJob::ACTION_NAME:
                    if (empty($oldLog->notes->file_date)) {
                        return ErrorResponse::json('Error: Missing old job parameters.', 400);
                    }
                    DB::commit();
                    GeneratePerformanceReportUploadJob::dispatch(
                        date: Carbon::parse($oldLog->notes->file_date),
                    );
                    break;

                case ProcessCallLogAPIJob::ACTION_NAME:
                    if (empty($oldLog->notes->file_date)) {
                        return ErrorResponse::json('Error: Missing old job parameters.', 400);
                    }
                    DB::commit();
                    ProcessCallLogAPIJob::dispatch(
                        date: CarbonImmutable::parse($oldLog->notes->file_date),
                    );
                    break;

                case ProcessRetreaverFileJob::ACTION_NAME:
                    if (empty($oldLog->notes->file) || empty($oldLog->notes->file_date)) {
                        return ErrorResponse::json('Error: Missing old job parameters.', 400);
                    }
                    DB::commit();
                    ProcessRetreaverFileJob::dispatch(
                        file: $oldLog->notes->file,
                        date: CarbonImmutable::parse($oldLog->notes->file_date),
                    );
                    break;

                case UploadOnScriptRecordingsJob::ACTION_NAME:
                    if (empty($oldLog->notes->file_date)) {
                        return ErrorResponse::json('Error: Missing old job parameters.', 400);
                    }
                    DB::commit();
                    UploadOnScriptRecordingsJob::dispatch(
                        date: CarbonImmutable::parse($oldLog->notes->file_date),
                    );
                    break;

                default:
                    DB::rollBack();

                    return ErrorResponse::json('Error: Unknown job type.', 400);
            }

            return response([]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorResponse::json('Processing error: '.$e->getMessage(), 400);
        }
    }
}
