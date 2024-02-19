<?php

namespace App\Mail;

use App\Helpers\ExcelHelper;
use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BillableHoursReportMail extends Mailable
{
    use Queueable, SerializesModels;

    private $startDate;
    private $endDate;
    private $filename;
    private $company;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($startDate, $endDate, $filename, $company)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->filename = $filename;
        $this->company = $company;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company_name = !empty($this->company->name) ? $this->company->name.' - ' : '';

        return $this
            ->subject("Billable Hours Report: {$company_name}{$this->startDate->format('n/j/Y')} - {$this->endDate->format('n/j/Y')}")
            ->markdown("emails.billable-hours", [
                'startDate' => $this->startDate->format('n/j/Y'),
                'endDate' => $this->endDate->format('n/j/Y'),
                'company' => $this->company,
            ])
            ->attach($this->filename, [
                'as' => "Billable Hours Report: {$company_name}{$this->startDate->format('Ymd')} - {$this->endDate->format('Ymd')}.xlsx",
                'mime' => ExcelHelper::CONTENT_TYPE,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_BILLABLE_HOURS_REPORT', true));
    }
}
