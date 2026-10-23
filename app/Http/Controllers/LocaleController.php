<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $locale = $request->input('locale', 'en');

        if (! in_array($locale, ['en', 'ar'])) {
            $locale = 'en';
        }

        cookie()->queue(cookie()->forever('locale', $locale));

        app()->setLocale($locale);

        return redirect()->back();
    }
}
