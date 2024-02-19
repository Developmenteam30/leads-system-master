<?php

namespace App\Mail;

use App\Models\DialerNotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MissingDispoReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        private CarbonImmutable $date,
        private $missing,
    ) {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject("Missing Disposition Report: {$this->date->format('n/j/Y')}")
            ->markdown("emails.missing-dispo-report", [
                'date' => $this->date->format('n/j/Y'),
                'dates' => $this->missing,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_MISSING_DISPOSITION_REPORT', true));
    }
}
