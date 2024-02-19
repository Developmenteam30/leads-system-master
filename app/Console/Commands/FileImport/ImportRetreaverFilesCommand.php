<?php

namespace App\Console\Commands\FileImport;

use App\Helpers\MicrosoftGraph;
use App\Jobs\ProcessRetreaverFileJob;
use App\Models\AuditLog;
use App\Models\DialerNotificationType;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\FileAttachment;
use Microsoft\Graph\Model\Message;

class ImportRetreaverFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-import:retreaver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Retreaver files from an Exchange mailbox';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $access_token = MicrosoftGraph::getOrRefreshAccessToken('sharepoint');

        $graph = new Graph();
        $graph->setAccessToken($access_token->getToken());

        $messages = $graph->createRequest("GET",
            "/me/mailFolders('Inbox')/messages?\$select=id,subject,sentDateTime,sender&\$filter=from/emailAddress/address eq 'rachel.villalobos@integriant.com'")
            ->addHeaders(["Content-Type" => "application/json"])
            ->setReturnType(Message::class)
            ->execute();

        /** @var Message $message */
        foreach ($messages as $message) {

            Log::info($message->getId().' '.$message->getSender()->getEmailAddress()->getAddress().' '.$message->getSubject());

            $attachments = $graph->createRequest("GET",
                "/me/messages/{$message->getId()}/attachments")
                ->addHeaders(["Content-Type" => "application/json"])
                ->setReturnType(FileAttachment::class)
                ->execute();

            /** @var FileAttachment $attachment */
            foreach ($attachments as $attachment) {
                if ('Acquiro_Retreaver_Data.csv' === $attachment->getName()) {
                    Log::info("ATTACHMENT: {$attachment->getName()}");

                    // Subtract one day as the files are sent the following day
                    $date = $message->getSentDateTime()->sub(new \DateInterval('P1D'));
                    $name = basename($attachment->getName());
                    $time = hrtime(true);
                    $path = Storage::disk('local')->path("uploads/{$time}-{$date->format('Y-m-d')}-{$name}");

                    File::put($path, base64_decode($attachment->getContentBytes()));

                    $log = new AuditLog();
                    $log->action = 'UPLOAD:DIALER-AGENT-PERFORMANCE';
                    $log->timestamp = Carbon::now();
                    $log->notes = json_encode([
                        'file' => $path,
                        'file_date' => $date->format('Y-m-d'),
                    ]);
                    $log->save();

                    ProcessRetreaverFileJob::dispatch(
                        file: $path,
                        email: DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_JOB_STATUS', true),
                        logId: $log->logId,
                        date: new CarbonImmutable($date),
                    );

                    $graph->createRequest("PATCH",
                        "/me/messages/{$message->getId()}")
                        ->addHeaders(["Content-Type" => "application/json"])
                        ->attachBody([
                            "isRead" => true,
                        ])
                        ->execute();

                    $graph->createRequest("POST",
                        "/me/messages/{$message->getId()}/move")
                        ->addHeaders(["Content-Type" => "application/json"])
                        ->attachBody([
                            "destinationId" => "deleteditems",
                        ])
                        ->execute();
                }
            }
        }

        return true;
    }
}
