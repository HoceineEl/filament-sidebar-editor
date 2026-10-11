<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../../lang');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'workbench');
    }
}
