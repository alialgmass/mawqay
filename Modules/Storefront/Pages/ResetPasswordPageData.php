<?php

namespace Modules\Storefront\Pages;

final class ResetPasswordPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly string $token,
        public readonly string $email,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'ResetPassword';
    }

    protected function pageProps(): array
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
        ];
    }
}
