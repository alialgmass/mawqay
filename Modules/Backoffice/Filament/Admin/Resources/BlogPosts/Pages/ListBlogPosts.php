<?php

namespace Modules\Backoffice\Filament\Admin\Resources\BlogPosts\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Backoffice\Filament\Admin\Resources\BlogPosts\BlogPostResource;

class ListBlogPosts extends ListRecords
{
    protected static string $resource = BlogPostResource::class;
}
