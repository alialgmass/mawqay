<?php

namespace Modules\Backoffice\Filament\Admin\Resources\BlogPosts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Backoffice\Filament\Admin\Resources\BlogPosts\BlogPostResource;
use Modules\Backoffice\Models\BlogPost;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    /**
     * Arabic titles do not slugify: fall back to a random readable slug.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : 'post-'.Str::lower(Str::random(8));

        /** @var BlogPost */
        return static::getModel()::create($data);
    }
}
