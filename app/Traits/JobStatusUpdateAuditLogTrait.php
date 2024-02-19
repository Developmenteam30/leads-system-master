<?php

namespace App\Traits;

use App\Models\AuditLog;

trait JobStatusUpdateAuditLogTrait
{
    protected $logId;

    /**
     * Handle a successful job.
     *
     * @return void
     */
    public function markLogAsSuccess()
    {
        if (!empty($this->logId)) {
            $log = AuditLog::find($this->logId);
            if ($log) {
                $notes = json_decode($log->notes);
                $notes->queueDetails = (object) [
                    'success' => true,
                    'message' => null,
                ];
                $log->notes = json_encode($notes);
                $log->save();
            }
        }
    }

    /**
     * Handle a failed job.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function markLogAsFailure(\Throwable $exception)
    {
        if (!empty($this->logId)) {
            $log = AuditLog::find($this->logId);
            if ($log) {
                $notes = json_decode($log->notes);
                $notes->queueDetails = (object) [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
                $log->notes = json_encode($notes);
                $log->save();
            }

        }
    }
}
