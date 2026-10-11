<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use HoceineEl\SidebarEditor\SidebarEditorPlugin;

class GlobalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('global')
            ->path('global')
            ->pages(FixturePages::all())
            ->plugin(SidebarEditorPlugin::make()->fixedMainGroup(false)->pinnedItems(['reports']));
    }
}
