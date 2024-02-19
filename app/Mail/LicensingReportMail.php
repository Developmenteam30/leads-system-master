<?php

namespace App\Mail;

use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class LicensingReportMail extends Mailable
{
    use Queueable, SerializesModels;

    private $date;
    private $agents;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($date, $agents)
    {
        $this->date = $date;
        $this->agents = $agents;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject("Licensing Report: {$this->date->format('n/j/Y')}")
            ->markdown("emails.licensing-report", [
                'date' => $this->date->format('n/j/Y'),
                'agents' => $this->agents,
            ])
            ->to(App::environment('production') ? 'licensing@acquiromedia.com' : DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_DEVELOPER'));
    }
}
