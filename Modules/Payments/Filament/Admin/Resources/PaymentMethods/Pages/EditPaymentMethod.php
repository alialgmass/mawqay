<?php

namespace Modules\Payments\Filament\Admin\Resources\PaymentMethods\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Payments\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;

class EditPaymentMethod extends EditRecord
{
    protected static string $resource = PaymentMethodResource::class;
}
