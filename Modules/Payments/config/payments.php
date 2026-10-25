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
];
