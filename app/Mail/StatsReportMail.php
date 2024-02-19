<?php

namespace App\Mail;

use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StatsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    private $date;
    private $stats;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($date, $stats)
    {
        $this->date = $date;
        $this->stats = $stats;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject("Stats Report: {$this->date->format('n/j/Y')}")
            ->markdown("emails.stats-report", [
                'date' => $this->date->format('n/j/Y'),
                'stats' => $this->stats,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_DAILY_STATS_REPORT', true));
    }
}
