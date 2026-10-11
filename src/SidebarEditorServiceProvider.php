<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor;

use Filament\Navigation\NavigationManager;
use Filament\Support\Facades\FilamentAsset;
use HoceineEl\SidebarEditor\Assets\SidebarEditorCss;
use HoceineEl\SidebarEditor\Navigation\LayoutNavigationManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SidebarEditorServiceProvider extends PackageServiceProvider
{
    public static string $name = 'sidebar-editor';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$name)
            ->hasTranslations()
            ->hasMigration('create_sidebar_editor_layouts_table');
    }

    public function packageRegistered(): void
    {
        $this->app->extend(NavigationManager::class, fn (NavigationManager $manager): NavigationManager => $manager instanceof LayoutNavigationManager
            ? $manager
            : new LayoutNavigationManager($manager));
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            SidebarEditorCss::make('sidebar-editor', __DIR__.'/../dist/sidebar-editor.css')->loadedOnRequest(),
        ], 'hoceineel/filament-sidebar-editor');
    }
}
