<?php

return [
    /*
     * When true, a Customer who registers with an email must verify it before
     * logging in. Off by default.
     */
    'verify_email' => (bool) env('CUSTOMER_EMAIL_VERIFICATION', false),

    /*
     * How long a password reset link and an email verification link last, in minutes.
     */
    'reset_minutes' => 60,
    'verification_minutes' => 60 * 24,

    'max_addresses' => 10,

    /*
     * Egypt's governorates: stored value => Arabic name.
     */
    'governorates' => [
        'cairo' => 'القاهرة',
        'giza' => 'الجيزة',
        'alexandria' => 'الإسكندرية',
        'qalyubia' => 'القليوبية',
        'port-said' => 'بورسعيد',
        'suez' => 'السويس',
        'dakahlia' => 'الدقهلية',
        'sharqia' => 'الشرقية',
        'gharbia' => 'الغربية',
        'monufia' => 'المنوفية',
        'beheira' => 'البحيرة',
        'kafr-el-sheikh' => 'كفر الشيخ',
        'damietta' => 'دمياط',
        'ismailia' => 'الإسماعيلية',
        'fayoum' => 'الفيوم',
        'beni-suef' => 'بني سويف',
        'minya' => 'المنيا',
        'assiut' => 'أسيوط',
        'sohag' => 'سوهاج',
        'qena' => 'قنا',
        'luxor' => 'الأقصر',
        'aswan' => 'أسوان',
        'red-sea' => 'البحر الأحمر',
        'new-valley' => 'الوادي الجديد',
        'matrouh' => 'مطروح',
        'north-sinai' => 'شمال سيناء',
        'south-sinai' => 'جنوب سيناء',
    ],
];
