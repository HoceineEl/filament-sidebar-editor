<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use HoceineEl\SidebarEditor\SidebarEditorPlugin;

class PersonalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('personal')
            ->path('personal')
            ->pages(FixturePages::all())
            ->plugin(SidebarEditorPlugin::make()->storePerUser()->slug('my-menu'));
    }
}
