<?php

namespace App\Mail;

use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LicenseSwapReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        private $date,
        private $subject_line,
        private $data
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
            ->subject($this->subject_line)
            ->markdown("emails.license-swap-report", [
                'date' => $this->date->format('n/j/Y'),
                'data' => $this->data,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_LICENSE_SWAP_REPORT', true));
    }
}
