<?php

namespace App\Mail;

use App\Helpers\ExcelHelper;
use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BillableTransferReport extends Mailable
{
    use Queueable, SerializesModels;

    private $date;
    private $filename;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($date, $filename)
    {
        $this->date = $date;
        $this->filename = $filename;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject("Missing Transfers Report: {$this->date->format('n/j/Y')}")
            ->markdown("emails.billable-transfer-report", [
                'date' => $this->date->format('n/j/Y'),
            ])
            ->attach($this->filename, [
                'as' => "Billable Transfers Report {$this->date->format('Ymd')}.xlsx",
                'mime' => ExcelHelper::CONTENT_TYPE,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_PAYROLL_REPORT', true));
    }
}
