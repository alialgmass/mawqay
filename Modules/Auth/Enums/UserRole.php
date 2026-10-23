<?php

namespace Modules\Auth\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case VendorOwner = 'vendor_owner';
}
