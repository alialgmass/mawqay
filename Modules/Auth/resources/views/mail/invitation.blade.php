<div dir="rtl" style="font-family: Tahoma, Arial, sans-serif; line-height: 1.8;">
    <p>{{ __('auth::invitations.mail_greeting', ['name' => $owner->name], 'ar') }}</p>
    <p>{{ __('auth::invitations.mail_body', ['store' => $invitation->store->name, 'hours' => \Modules\Auth\Models\Invitation::LIFETIME_HOURS], 'ar') }}</p>
    <p><a href="{{ $acceptUrl }}">{{ __('auth::invitations.mail_action', [], 'ar') }}</a></p>
    <p>{{ __('auth::invitations.mail_footer', [], 'ar') }}</p>
</div>
