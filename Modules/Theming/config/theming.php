<?php

return [
    /*
     * Where Theme folders live: one folder per Theme, each with a theme.json.
     */
    'path' => resource_path('themes'),

    /*
     * The Theme every Store falls back to. It is always active.
     */
    'default' => 'default',

    /*
     * The approved Arabic fonts a Vendor can choose from, self-hosted under
     * public/fonts/storefront. Keys are what is stored; the stack is the CSS value.
     */
    'fonts' => [
        'cairo' => ['label' => 'Cairo', 'stack' => "'Cairo', sans-serif"],
        'ibm-plex-sans-arabic' => ['label' => 'IBM Plex Sans Arabic', 'stack' => "'IBM Plex Sans Arabic', sans-serif"],
        'tajawal' => ['label' => 'Tajawal', 'stack' => "'Tajawal', sans-serif"],
        'almarai' => ['label' => 'Almarai', 'stack' => "'Almarai', sans-serif"],
        'noto-kufi-arabic' => ['label' => 'Noto Kufi Arabic', 'stack' => "'Noto Kufi Arabic', sans-serif"],
    ],

    'default_heading_font' => 'cairo',
    'default_body_font' => 'ibm-plex-sans-arabic',
    'default_corner_style' => 'soft',

    /*
     * Corner radius tokens per corner style, in pixels: small, medium, large.
     */
    'corners' => [
        'sharp' => [0, 0, 2],
        'soft' => [8, 12, 20],
    ],
];
