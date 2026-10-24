<?php

namespace Modules\Catalog\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case HiddenByAdmin = 'hidden_by_admin';
}
