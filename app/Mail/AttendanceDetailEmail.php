<?php

namespace App\Mail;

use App\Models\DialerNotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendanceDetailEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        private readonly CarbonImmutable $date,
        private $agents,
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
            ->subject("Attendance Detail Update: ".$this->date->format('n/j/Y'))
            ->markdown("emails.attendance-detail", [
                'agents' => $this->agents,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_ATTENDANCE_DETAIL_REPORT', true));
    }
}
