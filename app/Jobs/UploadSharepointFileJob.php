<?php

namespace App\Jobs;

use App\Helpers\MicrosoftGraph;
use App\Mail\JobStatus;
use App\Models\AuditLog;
use App\Models\DialerNotificationType;
use App\Traits\FailedJobTrait;
use App\Traits\JobStatusUpdateAuditLogTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Microsoft\Graph\Graph;

class UploadSharepointFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FailedJobTrait, JobStatusUpdateAuditLogTrait;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60 * 60 * 2;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected string $siteId,
        protected string $itemId,
        protected $file,
        protected string $inputFile,
    ) {

        $this->subject = 'Sharepoint Upload: '.basename($this->file);

        $log = new AuditLog();
        $log->action = 'UPLOAD:SHAREPOINT';
        $log->timestamp = Carbon::now();
        $log->notes = json_encode([
            'siteId' => $this->siteId,
            'itemId' => $this->itemId,
            'inputFile' => $this->inputFile,
            'file' => $this->file,
        ]);
        $log->save();

        $this->logId = $log->logId;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        ini_set('memory_limit', config('settings.job_memory_limit'));

        if (!file_exists($this->inputFile)) {
            Log::error("File does not exist: {$this->inputFile}");
            $this->fail(new \Exception("File does not exist: {$this->inputFile}"));

            return;
        }

        try {
            $access_token = MicrosoftGraph::getOrRefreshAccessToken('sharepoint');

            $graph = new Graph();
            $graph->setAccessToken($access_token->getToken());

            MicrosoftGraph::uploadLargeFile($graph, $this->siteId, $this->itemId, $this->inputFile, $this->file);

            $this->markLogAsSuccess();

            Mail::to($this->email)
                ->bcc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true))
                ->send(new JobStatus(
                    jobType: self::class,
                    status: 'Success',
                    file: basename($this->file),
                    subject: $this->subject,
                ));

        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
