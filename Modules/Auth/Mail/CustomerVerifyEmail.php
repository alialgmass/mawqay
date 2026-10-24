<?php

namespace Modules\Auth\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Auth\Models\Customer;

class CustomerVerifyEmail extends Mailable
{
    public function __construct(
        public readonly Customer $customer,
        public readonly string $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth::customers.verify_mail_subject', ['store' => $this->customer->store->name], 'ar'));
    }

    public function content(): Content
    {
        return new Content(view: 'auth::mail.customer-verify');
    }
}
