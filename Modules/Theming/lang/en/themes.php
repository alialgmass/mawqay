<?php

return [
    'label' => 'Theme',
    'plural' => 'Themes',
    'preview' => 'Preview',
    'name' => 'Name',
    'slug' => 'Slug',
    'version' => 'Version',
    'contract_version' => 'Contract',
    'is_active' => 'Active',
    'actions' => [
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'updated' => 'Theme updated',
        'rescan' => 'Re-scan themes folder',
        'rescanned' => ':count theme(s) registered.',
        'rescan_failed' => ':count invalid theme(s) were skipped.',
    ],
    'errors' => [
        'default_always_active' => 'The default theme is every store\'s fallback and cannot be deactivated.',
    ],
];
