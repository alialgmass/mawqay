<?php

namespace Modules\Storefront\Pages;

use Modules\Theming\Support\ThemeContract;

/**
 * What a Theme page receives: plain values only, never models, tagged with the
 * contract version it follows. Themes may rely on exactly these keys.
 */
abstract class PageData
{
    public const CONTRACT_VERSION = ThemeContract::VERSION;

    /**
     * @param  array<string, mixed>  $shell  Store identity, design tokens, navigation and URLs every page needs
     * @param  array{title: string, description: string|null, image: string|null}  $seo
     */
    public function __construct(
        public readonly array $shell,
        public readonly array $seo,
    ) {}

    /**
     * The Theme page this data is for, such as Home.
     */
    abstract public static function page(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function pageProps(): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->shell,
            'seo' => $this->seo,
            'contractVersion' => self::CONTRACT_VERSION,
            ...$this->pageProps(),
        ];
    }
}
