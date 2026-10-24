<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Support\ColorContrast;
use Modules\Tenancy\Models\Store;

class HomeController
{
    public function __invoke(): Response
    {
        $store = Store::current();
        $customer = Auth::guard('customer')->user();

        return Inertia::render('storefront/Home', [
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'logoUrl' => $store->logoUrl(),
                'faviconUrl' => $store->faviconUrl(),
                'footerText' => $store->footer_text,
                'contact' => [
                    'email' => $store->contact_email,
                    'phone' => $store->contact_phone,
                    'whatsapp' => $store->contact_whatsapp,
                ],
                'social' => $store->social_links ?? [],
            ],
            'customer' => $customer ? ['name' => $customer->name] : null,
            'tokens' => $this->tokensFor($store),
        ]);
    }

    /**
     * The default tokens with the Store's own colours laid over them, plus the
     * text colour that reads best on the primary colour.
     *
     * @return array<string, string>
     */
    private function tokensFor(Store $store): array
    {
        $tokens = config('storefront.tokens');

        if ($store->primary_color && ColorContrast::isHex($store->primary_color)) {
            $tokens['--color-primary'] = $store->primary_color;
        }

        if ($store->secondary_color && ColorContrast::isHex($store->secondary_color)) {
            $tokens['--color-secondary'] = $store->secondary_color;
        }

        $tokens['--color-on-primary'] = ColorContrast::readableTextOn($tokens['--color-primary']);
        $tokens['--color-on-secondary'] = ColorContrast::readableTextOn($tokens['--color-secondary']);

        return $tokens;
    }
}
