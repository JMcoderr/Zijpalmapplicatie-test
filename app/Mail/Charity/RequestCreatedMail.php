<?php

namespace App\Mail\Charity;

use App\Models\Charity\EventCost;
use App\Models\Charity\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Member $recipient;
    public string $iban;
    public string $account_name;
    public EventCost $event_cost;

    public function __construct(Member $recipient, string $iban, string $account_name, EventCost $event_cost)
    {
        $this->iban = $iban;
        $this->account_name = $account_name;
        $this->recipient = $recipient;
        $this->event_cost = $event_cost;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bevestiging van je aanvraag'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.charity.requestCreated', // <-- je eigen Blade view
            with: [
                'iban' => $this->iban,
                'account_name' => $this->account_name,
                'recipient' => $this->recipient,
                'event_cost_id' => $this->event_cost,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
