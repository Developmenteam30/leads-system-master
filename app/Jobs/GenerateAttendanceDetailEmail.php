<?php

namespace App\Jobs;

use App\Datasets\AttendanceDetailDataset;
use App\Mail\AttendanceDetailEmail;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GenerateAttendanceDetailEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $agents;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected CarbonImmutable $date
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $items = AttendanceDetailDataset::getDailyValues([
                'date' => $this->date,
            ]);

            Mail::send(new AttendanceDetailEmail(
                date: $this->date,
                agents: $items,
            ));
        } catch (\Throwable $e) {
            Log::error(self::class." Exception: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
