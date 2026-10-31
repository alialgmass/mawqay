<?php

return [
    /*
     * Submissions allowed per Order, rejected ones included.
     */
    'max_attempts' => 3,

    /*
     * Largest Receipt file, in kilobytes.
     */
    'max_receipt_kilobytes' => 5120,

    /*
     * How long a signed link to a Receipt stays valid, in minutes.
     */
    'receipt_link_minutes' => 5,

    /*
     * The private disk Receipts are kept on. Never a public disk.
     */
    'disk' => env('PAYMENTS_DISK', 'local'),

    /*
     * Fawaterk online gateway (hosted checkout). The hash key signs webhooks
     * (HMAC-SHA256); the client id and secret fetch OAuth tokens.
     */
    'fawaterk' => [
        'client_id' => env('FAWATERK_CLIENT_ID'),
        'client_secret' => env('FAWATERK_CLIENT_SECRET'),
        'hash_key' => env('FAWATERK_HASH_KEY'),
        'base_url' => env('FAWATERK_BASE_URL', 'https://app.fawaterk.com'),
    ],
];
