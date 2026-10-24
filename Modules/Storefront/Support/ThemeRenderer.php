<?php

namespace Modules\Storefront\Support;

use Inertia\Inertia;
use Modules\Storefront\Pages\NotFoundPageData;
use Modules\Storefront\Pages\PageData;
use Modules\Tenancy\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders page data with the request's Theme, falling back to the default
 * Theme's page when the chosen Theme lacks it or targets an unsupported contract.
 */
class ThemeRenderer
{
    public function __construct(private readonly StorefrontPresenter $presenter) {}

    public function render(PageData $data): Response
    {
        $component = $this->presenter->activeTheme()->componentFor($data::page());

        return Inertia::render($component, $data->toArray())->toResponse(request());
    }

    /**
     * The Theme's NotFound page with a 404 status, inside the current Store.
     */
    public function notFound(): Response
    {
        $store = Store::current();
        $title = __('storefront::pages.not_found').' | '.$store->name;

        return $this->render(new NotFoundPageData($this->presenter->shell(), $this->presenter->seo($title)))->setStatusCode(404);
    }
}
