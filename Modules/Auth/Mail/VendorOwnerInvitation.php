<?php

namespace Modules\Auth\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Auth\Models\Invitation;

class VendorOwnerInvitation extends Mailable
{
    public function __construct(
        public readonly User $owner,
        public readonly Invitation $invitation,
        public readonly string $acceptUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth::invitations.mail_subject', [], 'ar'));
    }

    public function content(): Content
    {
        return new Content(view: 'auth::mail.invitation');
    }
}
