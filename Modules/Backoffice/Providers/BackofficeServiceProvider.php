<?php

namespace Modules\Backoffice\Providers;

use Illuminate\Support\ServiceProvider;

class BackofficeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AdminPanelProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
