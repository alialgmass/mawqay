<?php

use Illuminate\Support\Facades\Route;
use Modules\Theming\Http\Controllers\ThemePreviewImageController;

Route::get('theme-previews/{slug}', ThemePreviewImageController::class)
    ->where('slug', '[a-z][a-z0-9-]*')
    ->name('theming.preview-image');
