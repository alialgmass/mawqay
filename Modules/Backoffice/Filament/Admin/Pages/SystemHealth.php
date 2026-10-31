<?php

namespace Modules\Backoffice\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Backoffice\Support\HealthReport;

/**
 * Whether the scheduler, queue and books checks are alive, for the Platform Admin.
 */
class SystemHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.admin_nav.system');
    }

    protected static ?string $slug = 'system-health';

    protected string $view = 'backoffice::admin.system-health';

    public static function getNavigationLabel(): string
    {
        return __('backoffice::panel.health.navigation');
    }

    public function getTitle(): string
    {
        return __('backoffice::panel.health.title');
    }

    /**
     * @return list<array{label: string, value: string, ok: bool}>
     */
    public function rows(): array
    {
        $checks = app(HealthReport::class)->checks();
        $when = fn ($at): string => $at === null ? __('backoffice::panel.health.never') : $at->diffForHumans();

        return [
            ['label' => __('backoffice::panel.health.queue_backlog'), 'value' => (string) $checks['queue_backlog']['value'], 'ok' => $checks['queue_backlog']['ok']],
            ['label' => __('backoffice::panel.health.failed_jobs'), 'value' => (string) $checks['failed_jobs']['value'], 'ok' => $checks['failed_jobs']['ok']],
            ['label' => __('backoffice::panel.health.scheduler'), 'value' => $when($checks['scheduler']['at']), 'ok' => $checks['scheduler']['ok']],
            ['label' => __('backoffice::panel.health.reconciliation'), 'value' => $when($checks['reconciliation']['at']), 'ok' => $checks['reconciliation']['ok']],
        ];
    }
}
