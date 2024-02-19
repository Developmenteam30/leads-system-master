<?php

namespace App\Console\Commands\FileImport;

use App\Jobs\UploadOnScriptRecordingsJob;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class UploadOnScriptRecordingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-import:upload-onscript-recordings {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload daily recordings via the OnScript API';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = !empty($this->argument('date')) ? CarbonImmutable::parse($this->argument('date')) : CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')));

        UploadOnScriptRecordingsJob::dispatch(
            date: $date,
        );

        return true;
    }
}
