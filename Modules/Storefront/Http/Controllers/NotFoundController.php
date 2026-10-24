<?php

namespace Modules\Storefront\Http\Controllers;

use Modules\Storefront\Support\ThemeRenderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Any address inside a Store that no page answers.
 */
class NotFoundController
{
    public function __invoke(ThemeRenderer $renderer): Response
    {
        return $renderer->notFound();
    }
}
