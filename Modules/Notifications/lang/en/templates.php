<?php

return [
    'open' => 'Open',
    'order_placed' => ['title' => 'We received your order', 'body' => 'Your order :number was placed. Complete the payment to confirm it.'],
    'receipt_received' => ['title' => 'We received your receipt', 'body' => 'We received the transfer receipt for order :number and it is under review.'],
    'payment_approved' => ['title' => 'Payment approved', 'body' => 'Payment for order :number was approved and the order is confirmed.'],
    'payment_rejected' => ['title' => 'Receipt rejected', 'body' => 'The receipt for order :number was rejected. Reason: :reason'],
    'receipt_awaiting_review' => ['title' => 'New receipt waiting for review', 'body' => 'A new receipt was uploaded for order :number.'],
    'order_paid' => ['title' => 'New paid order', 'body' => 'Order :number is paid. You can start preparing it.'],
    'order_shipped' => ['title' => 'Your order has shipped', 'body' => 'Order :number shipped with :courier.'],
    'order_delivered' => ['title' => 'Your order was delivered', 'body' => 'Order :number was delivered.'],
    'order_refunded' => ['title' => 'A refund was issued', 'body' => ':amount was refunded for order :number.'],
    'order_vendor_refunded' => ['title' => 'An order was refunded', 'body' => ':amount of order :number was refunded and your balance adjusted.'],
    'survey_invitation' => ['title' => 'How was your experience?', 'body' => 'Tell us about order :number. It takes a minute.'],
    'payout_requested' => ['title' => 'New payout request', 'body' => 'Store :store requested a payout of :amount.'],
    'payout_paid' => ['title' => 'Your payout was sent', 'body' => ':amount was transferred to you.'],
    'payout_rejected' => ['title' => 'Your payout was rejected', 'body' => 'The payout of :amount was rejected. Reason: :reason'],
    'low_rating' => ['title' => 'Low rating', 'body' => 'Order :number was rated :rating out of 5.'],
    'reconciliation_failed' => ['title' => 'The books do not match', 'body' => 'Reconciliation found :count difference(s). Check the result in the Admin Panel.'],
];
