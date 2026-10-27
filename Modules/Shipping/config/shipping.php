<?php

use Modules\Shipping\Carriers\ManualShippingProvider;

return [
    /*
     * The carrier integration in use. Manual means the Vendor types the courier and tracking number;
     * a courier gateway implements the same contract and is named here.
     */
    'provider' => ManualShippingProvider::class,
];
