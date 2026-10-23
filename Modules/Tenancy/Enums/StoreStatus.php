<?php

namespace Modules\Tenancy\Enums;

enum StoreStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';
}
