<?php

namespace App\Mail;

use App\Models\DialerNotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LicenseSwapAssignmentsEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        private $licenses
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
            ->subject("License Swap Assignments: ".CarbonImmutable::now(new \DateTimeZone(config('settings.timezone.local')))->format('n/j/Y'))
            ->markdown("emails.license-swap-assignments", [
                'licenses' => $this->licenses,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_LICENSE_SWAP_ASSIGNMENTS', true));
    }
}
