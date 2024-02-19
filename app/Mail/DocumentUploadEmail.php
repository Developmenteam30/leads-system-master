<?php

namespace App\Mail;

use App\Models\DialerDocument;
use App\Models\DialerNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentUploadEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        private DialerDocument $document,
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
            ->subject("Document Upload - {$this->document->documentable->agent_name} - {$this->document->documentType->name}")
            ->markdown("emails.document-upload", [
                'document' => $this->document,
            ])
            ->to(DialerNotificationType::getEmailsForNotificationType('NOTIFICATION_TYPE_DOCUMENT_UPLOAD', true));
    }
}
