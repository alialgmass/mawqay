<?php

return [
    /*
     * Whether every Platform Admin must set up and use a second factor to reach the Admin Panel.
     * Leave on in every real environment; automated tests switch it off unless they test it.
     */
    'admin_mfa_required' => env('ADMIN_MFA_REQUIRED', false),
];
