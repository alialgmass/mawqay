<div dir="rtl" style="font-family: Tahoma, Arial, sans-serif; line-height: 1.8;">
    <p>{{ __('auth::customers.mail_greeting', ['name' => $customer->name], 'ar') }}</p>
    <p>{{ __('auth::customers.verify_mail_body', ['store' => $customer->store->name], 'ar') }}</p>
    <p><a href="{{ $verifyUrl }}">{{ __('auth::customers.verify_mail_action', [], 'ar') }}</a></p>
    <p>{{ __('auth::customers.mail_footer', [], 'ar') }}</p>
</div>
