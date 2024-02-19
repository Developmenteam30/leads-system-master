<?php

namespace App\Mail;

use App\Helpers\ExcelHelper;
use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class PayrollReport extends Mailable
{
    use Queueable, SerializesModels;

    private $startDate;
    private $endDate;
    private $filename;
    private $campaign;
    private $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($startDate, $endDate, $filename, $campaign, $user)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->filename = $filename;
        $this->campaign = $campaign;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $campaign_name = !empty($this->campaign->name) ? $this->campaign->name.' - ' : '';

        return $this
            ->subject("Payroll Report: {$campaign_name}{$this->startDate->format('n/j/Y')} - {$this->endDate->format('n/j/Y')}")
            ->markdown("emails.payroll-report", [
                'startDate' => $this->startDate->format('n/j/Y'),
                'endDate' => $this->endDate->format('n/j/Y'),
                'campaign' => $this->campaign,
                'user' => $this->user,
            ])
            ->attach($this->filename, [
                'as' => "Payroll Report {$campaign_name}{$this->startDate->format('Ymd')} - {$this->endDate->format('Ymd')}.xlsx",
                'mime' => ExcelHelper::CONTENT_TYPE,
            ])
            ->to(App::environment('production') ? $this->user->email : DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_DEVELOPER'))
            ->cc(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_PAYROLL_REPORT', true));
    }
}
