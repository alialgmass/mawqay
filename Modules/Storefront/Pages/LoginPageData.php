<?php

namespace Modules\Storefront\Pages;

final class LoginPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly ?string $redirect,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Login';
    }

    protected function pageProps(): array
    {
        return [
            'redirect' => $this->redirect,
        ];
    }
}
