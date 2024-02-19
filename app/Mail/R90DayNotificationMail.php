<?php

namespace App\Mail;

use App\Models\DialerNotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class R90DayNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        protected CarbonImmutable $next_week_monday,
        protected CarbonImmutable $next_week_sunday,
        protected $agents,
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
            ->subject("90-Day Anniversaries: {$this->next_week_monday->format('n/j/Y')} - {$this->next_week_sunday->format('n/j/Y')}")
            ->markdown("emails.90-day-notification", [
                'next_week_monday' => $this->next_week_monday,
                'next_week_sunday' => $this->next_week_sunday,
                'agents' => $this->agents,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_ANNIVERSARY_DAY', true));
    }
}
