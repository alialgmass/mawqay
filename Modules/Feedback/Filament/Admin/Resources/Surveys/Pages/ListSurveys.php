<?php

namespace Modules\Feedback\Filament\Admin\Resources\Surveys\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Feedback\Filament\Admin\Resources\Surveys\SurveyResource;

class ListSurveys extends ListRecords
{
    protected static string $resource = SurveyResource::class;
}
