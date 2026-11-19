<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Majiteľovi: žiadosť sme nemohli schváliť. */
class BusinessRejected extends Mailable
{
    use Queueable;

    public function __construct(public Business $business) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'K registrácii podniku '.$this->business->name.' potrebujeme doplnenie');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.business-rejected', with: [
            'business' => $this->business->name,
            'reason' => $this->business->rejection_reason,
            'url' => route('panel.profile'),
        ]);
    }
}
