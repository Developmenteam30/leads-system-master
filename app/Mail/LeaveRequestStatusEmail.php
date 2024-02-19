<?php

namespace App\Mail;

use App\Models\DialerLeaveRequest;
use App\Models\DialerNotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class LeaveRequestStatusEmail extends Mailable
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
        $status = strtoupper($this->request->status?->name);

        // 8255: Leave Request status changes go to the NOTIFICATION_TYPE_LEAVE_REQUEST_STATUS_CHANGE notification type,
        // plus to the agent, the agent's manager, and the agent's team leads.
        $cc = DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_LEAVE_REQUEST_STATUS_CHANGE', true);
        if (App::environment('production')) {
            if (!empty($this->request->agent->team->manager->email)) {
                $cc->push(new Address($this->request->agent->team->manager->email, $this->request->agent->team->manager->agent_name));
            }
            if (!empty($this->request->agent->team->leads)) {
                foreach ($this->request->agent->team->leads as $lead) {
                    if (!empty($lead->email)) {
                        $cc->push(new Address($lead->email, $lead->agent_name));
                    }
                }
            }
        }

        return $this
            ->from(config('mail.from.hr_address'), config('mail.from.name'))
            ->subject("{$status} - {$this->request->agent?->agent_name} - {$start_date} - {$this->request->type?->name}")
            ->markdown("emails.leave-request-status", [
                'request' => $this->request,
            ])
            ->to(App::environment('production') ? new Address($this->request->agent->email, $this->request->agent->agent_name) : config('settings.developer_email'))
            ->cc($cc);
    }
}
