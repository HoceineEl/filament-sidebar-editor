<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;

class OtherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('other')
            ->path('other')
            ->pages(FixturePages::all());
    }
}
