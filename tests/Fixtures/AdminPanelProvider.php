<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use HoceineEl\SidebarEditor\SidebarEditorPlugin;

class AdminPanelProvider extends PanelProvider
{
    public static bool $allowed = true;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->tenant(Team::class, slugAttribute: 'slug')
            ->navigationGroups(['sales' => 'Sales', 'inventory' => 'Inventory', 'insights' => 'Insights', 'settings' => 'Settings'])
            ->pages(FixturePages::all())
            ->plugin(SidebarEditorPlugin::make()
                ->storeOnTenant()
                ->navigationGroup('settings')
                ->authorize(fn (): bool => static::$allowed));
    }
}
