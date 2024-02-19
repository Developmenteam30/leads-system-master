<?php

namespace App\Mail;

use App\Models\DialerLeaveRequest;
use App\Models\DialerNotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestSubmittedEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        private DialerLeaveRequest $request,
    ) {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $start_date = CarbonImmutable::parse($this->request->formattedStartDate)->format('n/j');

        return $this
            ->from(config('mail.from.hr_address'), config('mail.from.name'))
            ->subject("LR - {$this->request->agent?->agent_name} - {$start_date} - {$this->request->type?->name}")
            ->markdown("emails.leave-request-submitted", [
                'request' => $this->request,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_LEAVE_REQUEST_SUBMITTED', true));
    }
}
