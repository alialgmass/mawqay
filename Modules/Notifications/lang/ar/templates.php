<?php

/*
 * Every notification the Platform sends, in Arabic, in one place. Each key is an event in the
 * NotificationCatalogue; a test fails if an event has no template here.
 */
return [
    'open' => 'فتح',
    'order_placed' => ['title' => 'تم استلام طلبك', 'body' => 'تم تسجيل طلبك :number. أكملي الدفع لتأكيده.'],
    'receipt_received' => ['title' => 'استلمنا إيصالك', 'body' => 'استلمنا إيصال التحويل للطلب :number وهو قيد المراجعة.'],
    'payment_approved' => ['title' => 'تم قبول الدفع', 'body' => 'تم قبول الدفع للطلب :number وأصبح الطلب مؤكدًا.'],
    'payment_rejected' => ['title' => 'تم رفض الإيصال', 'body' => 'تم رفض إيصال الطلب :number. السبب: :reason'],
    'receipt_awaiting_review' => ['title' => 'إيصال جديد بانتظار المراجعة', 'body' => 'رُفع إيصال جديد للطلب :number.'],
    'order_paid' => ['title' => 'طلب جديد مدفوع', 'body' => 'تم دفع الطلب :number. يمكنك البدء في تجهيزه.'],
    'order_shipped' => ['title' => 'تم شحن طلبك', 'body' => 'تم شحن الطلب :number عبر :courier.'],
    'order_delivered' => ['title' => 'تم تسليم طلبك', 'body' => 'تم تسليم الطلب :number.'],
    'order_refunded' => ['title' => 'تم استرجاع مبلغ', 'body' => 'تم استرجاع :amount للطلب :number.'],
    'order_vendor_refunded' => ['title' => 'تم استرجاع طلب', 'body' => 'تم استرجاع :amount من الطلب :number وتم تعديل رصيدك.'],
    'survey_invitation' => ['title' => 'كيف كانت تجربتك؟', 'body' => 'شاركينا رأيك في الطلب :number، ويستغرق ذلك دقيقة واحدة.'],
    'payout_requested' => ['title' => 'طلب صرف جديد', 'body' => 'طلب المتجر :store صرف :amount.'],
    'payout_paid' => ['title' => 'تم صرف مستحقاتك', 'body' => 'تم تحويل :amount إليك.'],
    'payout_rejected' => ['title' => 'تم رفض طلب الصرف', 'body' => 'تم رفض طلب صرف :amount. السبب: :reason'],
    'low_rating' => ['title' => 'تقييم منخفض', 'body' => 'حصل الطلب :number على تقييم :rating من 5.'],
    'reconciliation_failed' => ['title' => 'فروقات في دفتر الحسابات', 'body' => 'وجدت المطابقة :count فرق. راجع نتيجة المطابقة في لوحة الإدارة.'],
];
