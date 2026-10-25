<?php

return [
    'open' => 'Open',
    'receipt_received' => [
        'title' => 'We received your receipt',
        'body' => 'We received the transfer receipt for order :number and it is under review.',
    ],
    'approved' => [
        'title' => 'Payment approved',
        'body' => 'Payment for order :number was approved and the order is confirmed.',
    ],
    'rejected' => [
        'title' => 'Receipt rejected',
        'body' => 'The receipt for order :number was rejected. Reason: :reason',
    ],
    'awaiting_review' => [
        'title' => 'New receipt waiting for review',
        'body' => 'A new receipt was uploaded for order :number.',
    ],
    'order_paid' => [
        'title' => 'New paid order',
        'body' => 'Order :number is paid. You can start preparing it.',
    ],
];
