<?php

return [
    /*
     * Store Slugs that can never be used because they collide with Platform
     * routes. Add to this list whenever a new top-level route is introduced.
     */
    'reserved_slugs' => [
        'admin',
        'vendor',
        'api',
        'login',
        'register',
        'logout',
        'storage',
        'assets',
        'build',
        'up',
        'livewire',
        'filament',
        'invitations',
        'theme-previews',
        'payment-receipts',
    ],
];
