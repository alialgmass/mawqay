<?php

namespace Modules\Sales\Enums;

enum ShippingRateKind: string
{
    case Flat = 'flat';
    case Governorate = 'governorate';
}
