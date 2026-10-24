<?php

namespace Modules\Auth\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Auth\Models\Customer;
use Modules\Tenancy\Models\Store;

class CustomerPasswordReset extends Mailable
{
    public function __construct(
        public readonly Store $store,
        public readonly Customer $customer,
        public readonly string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth::customers.reset_mail_subject', ['store' => $this->store->name], 'ar'));
    }

    public function content(): Content
    {
        return new Content(view: 'auth::mail.customer-reset');
    }
}
