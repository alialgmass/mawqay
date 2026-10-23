<?php

/**
 * Shared Filament admin-panel copy: navigation groups and the Access-Control
 * resources (Users / Roles / Permissions) that live in app/Filament.
 * Module-owned resources keep their strings in their own module lang directory.
 */
return [
    'brand' => 'THOB Admin',

    'nav' => [
        'verification' => 'Verification & Accounts',
        'orders' => 'Orders',
        'promotion' => 'Promotion',
        'content' => 'Content',
        'taxonomy' => 'Taxonomy',
        'moderation' => 'Moderation',
        'billing' => 'Billing',
        'access_control' => 'Access Control',
        'system' => 'System',
    ],

    'tabs' => [
        'all' => 'All',
    ],

    'enums' => [
        'verification_request_status' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
        'order_status' => [
            'created' => 'New',
            'seller_confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'shipped_or_delivered' => 'Shipped / delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
        ],
        'report_status' => [
            'open' => 'Open',
            'investigating' => 'Investigating',
            'resolved' => 'Resolved',
            'dismissed' => 'Dismissed',
        ],
        'promotion_request_status' => [
            'pending' => 'Pending',
            'activated' => 'Activated',
            'rejected' => 'Rejected',
        ],
        'account_type_change_status' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
        'product_status' => [
            'draft' => 'Draft',
            'pending_review' => 'Pending review',
            'published' => 'Published',
            'hidden' => 'Hidden',
            'unavailable' => 'Unavailable',
            'rejected' => 'Rejected',
        ],
        'subscription_status' => [
            'active' => 'Active',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'restricted' => 'Restricted',
        ],
        'user_status' => [
            'pending_type_selection' => 'Pending type selection',
            'active' => 'Active',
            'suspended' => 'Suspended',
        ],
        'account_type' => [
            'importer' => 'Importer',
            'wholesaler' => 'Wholesaler',
            'retailer' => 'Retailer',
            'customer' => 'Customer',
        ],
        'featured_placement_source' => [
            'admin_curated' => 'Admin curated',
            'purchased' => 'Purchased',
        ],
        'billing_cycle' => [
            'monthly' => 'Monthly',
            'annual' => 'Annual',
        ],
    ],

    'filters' => [
        'created_at' => 'Created date',
        'from' => 'From',
        'until' => 'Until',
    ],

    'sections' => [
        'account' => 'Account',
        'access' => 'Access & status',
        'role_details' => 'Role details',
        'permission_details' => 'Permission details',
        'media' => 'Banner & link',
        'schedule' => 'Schedule',
        'visibility' => 'Visibility',
        'request' => 'Request',
        'review' => 'Review',
        'target' => 'Target',
        'content_ar' => 'Arabic content',
        'content_en' => 'English content',
        'versioning' => 'Versioning',
    ],

    'common' => [
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'guard_name' => 'Guard',
    ],

    'user' => [
        'label' => 'User',
        'plural' => 'Users',
        'fields' => [
            'phone' => 'Phone',
            'email' => 'Email address',
            'password' => 'Password',
            'account_type' => 'Account type',
            'language' => 'Language',
            'status' => 'Status',
        ],
        'actions' => [
            'suspend' => 'Suspend',
            'suspend_confirm' => 'I understand this immediately revokes this account\'s access.',
            'suspended' => 'Account suspended.',
            'reactivate' => 'Reactivate',
            'reactivated' => 'Account reactivated.',
        ],
    ],

    'role' => [
        'label' => 'Role',
        'plural' => 'Roles',
        'fields' => [
            'name' => 'Name',
        ],
    ],

    'permission' => [
        'label' => 'Permission',
        'plural' => 'Permissions',
        'fields' => [
            'name' => 'Name',
        ],
    ],
];
