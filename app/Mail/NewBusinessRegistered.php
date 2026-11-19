<?php

namespace App\Mail;

use App\Enums\BusinessRole;
use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Správcom: nový podnik čaká na overenie. */
class NewBusinessRegistered extends Mailable
{
    use Queueable;

    public function __construct(public Business $business) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nový podnik čaká na overenie: '.$this->business->name);
    }

    public function content(): Content
    {
        $owner = $this->business->members()->with('user')->where('role', BusinessRole::Owner)->first()?->user;

        return new Content(markdown: 'mail.business-registered', with: [
            'business' => $this->business,
            'owner' => $owner,
            'url' => route('admin.businesses'),
        ]);
    }
}
