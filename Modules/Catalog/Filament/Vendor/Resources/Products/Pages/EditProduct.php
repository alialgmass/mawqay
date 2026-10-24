<?php

namespace Modules\Catalog\Filament\Vendor\Resources\Products\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
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

    protected function getHeaderActions(): array
    {
        return [
            $this->statusAction('publish', PublishProduct::class, 'published', ProductStatus::Draft),
            $this->statusAction('unpublish', UnpublishProduct::class, 'unpublished', ProductStatus::Published),
            DeleteAction::make(),
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
