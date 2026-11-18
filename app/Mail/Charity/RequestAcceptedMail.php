<?php

namespace App\Mail\Charity;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestModel;

    public function __construct($requestModel)
    {
        $this->requestModel = $requestModel;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Je aanvraag is goedgekeurd'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.charity.requestAccepted',
            with: [
                'requestModel' => $this->requestModel,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}



