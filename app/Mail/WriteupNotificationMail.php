<?php

namespace App\Mail;

use App\Models\DialerAgentWriteup;
use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class WriteupNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    private DialerAgentWriteup $writeup;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(DialerAgentWriteup $writeup)
    {
        $this->writeup = $writeup;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $message = $this
            ->from(config('mail.from.hr_address'), config('mail.from.name'))
            ->subject("Agent Write-Up: {$this->writeup->agent->agent_name} - {$this->writeup->date}")
            ->markdown("emails.writeup-notification", [
                'writeup' => $this->writeup,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_WRITE_UP_NOTIFICATION', true));

        if (App::environment('production') && !empty($this->writeup->agent->team->manager->email)) {
            $message->cc($this->writeup->agent->team->manager->email);
        }

        return $message;
    }
}
