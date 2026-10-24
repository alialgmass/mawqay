<?php

namespace Modules\Theming\Exceptions;

use RuntimeException;

class InvalidThemeManifest extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Invalid theme manifest: '.implode('; ', $errors));
    }
}
