<?php

return [
    'brand' => 'إدارة ثوب',

    'nav' => [
        'verification' => 'التوثيق والحسابات',
        'orders' => 'الطلبات',
        'promotion' => 'الترويج',
        'content' => 'المحتوى',
        'taxonomy' => 'التصنيفات',
        'moderation' => 'المراجعة',
        'billing' => 'الاشتراكات',
        'access_control' => 'الصلاحيات',
        'system' => 'النظام',
    ],

    'tabs' => [
        'all' => 'الكل',
    ],

    'enums' => [
        'verification_request_status' => [
            'pending' => 'قيد المراجعة',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
        ],
        'order_status' => [
            'created' => 'جديد',
            'seller_confirmed' => 'تم التأكيد',
            'preparing' => 'قيد التجهيز',
            'shipped_or_delivered' => 'تم الشحن',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'rejected' => 'مرفوض',
        ],
        'report_status' => [
            'open' => 'مفتوح',
            'investigating' => 'قيد التحقيق',
            'resolved' => 'تم الحل',
            'dismissed' => 'مستبعد',
        ],
        'promotion_request_status' => [
            'pending' => 'قيد المراجعة',
            'activated' => 'مفعّل',
            'rejected' => 'مرفوض',
        ],
        'account_type_change_status' => [
            'pending' => 'قيد المراجعة',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
        ],
        'product_status' => [
            'draft' => 'مسودة',
            'pending_review' => 'بانتظار المراجعة',
            'published' => 'منشور',
            'hidden' => 'مخفي',
            'unavailable' => 'غير متاح',
            'rejected' => 'مرفوض',
        ],
        'subscription_status' => [
            'active' => 'نشط',
            'expired' => 'منتهي',
            'cancelled' => 'ملغي',
            'restricted' => 'مقيّد',
        ],
        'user_status' => [
            'pending_type_selection' => 'بانتظار اختيار النوع',
            'active' => 'نشط',
            'suspended' => 'موقوف',
        ],
        'account_type' => [
            'importer' => 'مستورد',
            'wholesaler' => 'تاجر جملة',
            'retailer' => 'تاجر تجزئة',
            'customer' => 'عميل',
        ],
        'featured_placement_source' => [
            'admin_curated' => 'اختيار الإدارة',
            'purchased' => 'مدفوع',
        ],
        'billing_cycle' => [
            'monthly' => 'شهري',
            'annual' => 'سنوي',
        ],
    ],

    'filters' => [
        'created_at' => 'تاريخ الإنشاء',
        'from' => 'من',
        'until' => 'إلى',
    ],

    'sections' => [
        'account' => 'الحساب',
        'access' => 'الدخول والحالة',
        'role_details' => 'بيانات الدور',
        'permission_details' => 'بيانات الصلاحية',
        'media' => 'البنر والرابط',
        'schedule' => 'الجدولة',
        'visibility' => 'الظهور',
        'request' => 'الطلب',
        'review' => 'المراجعة',
        'target' => 'المستهدف',
        'content_ar' => 'المحتوى العربي',
        'content_en' => 'المحتوى الإنجليزي',
        'versioning' => 'الإصدار',
    ],

    'common' => [
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'guard_name' => 'الحارس',
    ],

    'user' => [
        'label' => 'مستخدم',
        'plural' => 'المستخدمون',
        'fields' => [
            'phone' => 'رقم الهاتف',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'account_type' => 'نوع الحساب',
            'language' => 'اللغة',
            'status' => 'الحالة',
        ],
        'actions' => [
            'suspend' => 'إيقاف',
            'suspend_confirm' => 'أفهم أن هذا يوقف وصول هذا الحساب فورًا.',
            'suspended' => 'تم إيقاف الحساب.',
            'reactivate' => 'إعادة تفعيل',
            'reactivated' => 'تمت إعادة تفعيل الحساب.',
        ],
    ],

    'role' => [
        'label' => 'دور',
        'plural' => 'الأدوار',
        'fields' => [
            'name' => 'الاسم',
        ],
    ],

    'permission' => [
        'label' => 'صلاحية',
        'plural' => 'الصلاحيات',
        'fields' => [
            'name' => 'الاسم',
        ],
    ],
];
