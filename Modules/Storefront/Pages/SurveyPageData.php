<?php

namespace Modules\Storefront\Pages;

final class SurveyPageData extends PageData
{
    /**
     * @param  array<string, mixed>  $shell
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     * @param  string  $state  form, answered, thanks, expired or invalid
     */
    public function __construct(
        array $shell,
        array $seo,
        public readonly string $state,
        public readonly ?string $orderNumber = null,
        public readonly ?string $submitUrl = null,
    ) {
        parent::__construct($shell, $seo);
    }

    public static function page(): string
    {
        return 'Survey';
    }

    protected function pageProps(): array
    {
        return [
            'survey' => [
                'state' => $this->state,
                'orderNumber' => $this->orderNumber,
                'submitUrl' => $this->submitUrl,
            ],
        ];
    }
}
