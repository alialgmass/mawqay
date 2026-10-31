<?php

namespace Modules\Backoffice\Filament\Admin\Resources\BlogPosts\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Backoffice\Filament\Admin\Resources\BlogPosts\BlogPostResource;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;
}
