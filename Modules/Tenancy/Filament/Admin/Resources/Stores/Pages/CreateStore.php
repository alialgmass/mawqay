<?php

namespace Modules\Tenancy\Filament\Admin\Resources\Stores\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Contracts\VendorOnboarding;
use Modules\Tenancy\Filament\Admin\Resources\Stores\StoreResource;

class CreateStore extends CreateRecord
{
    protected static string $resource = StoreResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(VendorOnboarding::class)->createStoreWithOwner($data);
    }
}
