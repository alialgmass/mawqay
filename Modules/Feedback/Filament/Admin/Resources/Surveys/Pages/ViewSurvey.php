<?php

namespace Modules\Feedback\Filament\Admin\Resources\Surveys\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Feedback\Filament\Admin\Resources\Surveys\SurveyResource;

class ViewSurvey extends ViewRecord
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [SurveyResource::copyLinkAction()];
    }
}
