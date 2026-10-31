<?php

return [
    /*
     * The text before the sequence number in an Order number: ORD-000001.
     */
    'order_prefix' => 'ORD',

    /*
     * How a Customer says they will pay. The Platform's accounts and the
     * instructions for each arrive with payments.
     */
    'payment_methods' => [
        'vodafone_cash' => 'Vodafone Cash',
        'instapay' => 'InstaPay',
        // Online card/wallet checkout; offered only once its credentials are set.
        ...(env('FAWATERK_CLIENT_ID') ? ['fawaterk' => 'Fawaterk'] : []),
    ],

    'currency' => 'EGP',
];
