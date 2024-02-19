<?php

namespace App\Mail;

use App\Models\DialerAgentWriteup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class WriteupAgentNotificationMail extends Mailable
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
            ->to(App::environment('production') ? new Address($this->writeup->agent->email, $this->writeup->agent->agent_name) : config('settings.developer_email'));

        return $message;
    }
}
