<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Majiteľovi: podnik je overený. */
class BusinessApproved extends Mailable
{
    use Queueable;

    public function __construct(public Business $business) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->business->name.' je overený – môžete zverejniť ponuku');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.business-approved', with: ['business' => $this->business->name, 'url' => route('panel.offer')]);
    }
}
