<?php

namespace Modules\Catalog\Filament\Vendor\Resources\Products\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Actions\PublishProduct;
use Modules\Catalog\Actions\UnpublishProduct;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Filament\Vendor\Resources\Products\ProductResource;
use Modules\Catalog\Models\Product;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * A Published Product that lost its last Variant or image is no longer
     * buyable, so it goes back to Draft instead of staying public.
     */
    protected function afterSave(): void
    {
        $product = $this->getRecord();

        if ($product->status === ProductStatus::Published && (! $product->variants()->exists() || ! $product->media()->exists())) {
            app(UnpublishProduct::class)($product);
            $this->refreshFormData(['status']);
            Notification::make()->title(__('catalog::products.actions.unpublished_incomplete'))->warning()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->statusAction('publish', PublishProduct::class, 'published', ProductStatus::Draft),
            $this->statusAction('unpublish', UnpublishProduct::class, 'unpublished', ProductStatus::Published),
        ];
    }

    /**
     * @param  class-string  $domainAction
     */
    private function statusAction(string $name, string $domainAction, string $notification, ProductStatus $visibleWhen): Action
    {
        return Action::make($name)
            ->label(__("catalog::products.actions.{$name}"))
            ->visible(fn (Product $record): bool => $record->status === $visibleWhen)
            ->action(function (Product $record) use ($domainAction, $notification): void {
                try {
                    app($domainAction)($record);
                } catch (ValidationException $exception) {
                    Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();

                    return;
                }

                $this->refreshFormData(['status']);
                Notification::make()->title(__("catalog::products.actions.{$notification}"))->success()->send();
            });
    }
}
