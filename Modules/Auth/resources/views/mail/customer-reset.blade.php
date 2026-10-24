<div dir="rtl" style="font-family: Tahoma, Arial, sans-serif; line-height: 1.8;">
    <p>{{ __('auth::customers.mail_greeting', ['name' => $customer->name], 'ar') }}</p>
    <p>{{ __('auth::customers.reset_mail_body', ['store' => $store->name, 'minutes' => config('customers.reset_minutes')], 'ar') }}</p>
    <p><a href="{{ $resetUrl }}">{{ __('auth::customers.reset_mail_action', [], 'ar') }}</a></p>
    <p>{{ __('auth::customers.mail_footer', [], 'ar') }}</p>
</div>
