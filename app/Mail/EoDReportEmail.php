<?php

namespace App\Mail;

use App\Models\DialerEodReport;
use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EoDReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private DialerEodReport $report,
    ) {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        Log::info("Sending EoD Report Email", [
            'report' => $this->report,
        ]);

        return $this
            ->subject("End of Day Report - {$this->report->team->name} - {$this->report->reportDate->format('n/j/Y')}")
            ->markdown("emails.eod-report", [
                'report' => $this->report,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_EOD_REPORT ', true));
    }
}

