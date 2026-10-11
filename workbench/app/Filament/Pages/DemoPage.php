<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Str;

abstract class DemoPage extends Page
{
    protected string $view = 'workbench::page';

    public static function getNavigationLabel(): string
    {
        return __(Str::headline(class_basename(static::class)));
    }
}
