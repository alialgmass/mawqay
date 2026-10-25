<?php

return [
    'open' => 'فتح',
    'receipt_received' => [
        'title' => 'استلمنا إيصالك',
        'body' => 'استلمنا إيصال التحويل للطلب :number وهو قيد المراجعة.',
    ],
    'approved' => [
        'title' => 'تم قبول الدفع',
        'body' => 'تم قبول الدفع للطلب :number وأصبح الطلب مؤكدًا.',
    ],
    'rejected' => [
        'title' => 'تم رفض الإيصال',
        'body' => 'تم رفض إيصال الطلب :number. السبب: :reason',
    ],
    'awaiting_review' => [
        'title' => 'إيصال جديد بانتظار المراجعة',
        'body' => 'رُفع إيصال جديد للطلب :number.',
    ],
    'order_paid' => [
        'title' => 'طلب جديد مدفوع',
        'body' => 'تم دفع الطلب :number. يمكنك البدء في تجهيزه.',
    ],
];
