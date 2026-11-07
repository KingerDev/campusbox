<?php

namespace App\Mail;

use App\Models\BusinessMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TeamInvitation extends Mailable
{
    use Queueable;

    public function __construct(public BusinessMember $member) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pozvánka do tímu '.$this->member->business->name.' na CampusBoxe');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.team-invitation', with: [
            'business' => $this->member->business->name,
            'role' => mb_strtolower($this->member->role->label()),
            'url' => route('panel.invitation', $this->member->invite_token),
        ]);
    }
}
