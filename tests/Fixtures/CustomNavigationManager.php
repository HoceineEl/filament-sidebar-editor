<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;

class CustomNavigationManager extends NavigationManager
{
    /**
     * @return array<NavigationGroup>
     */
    public function get(): array
    {
        return [...parent::get(), NavigationGroup::make('Extra')->items([NavigationItem::make('Help')->url('/admin/acme/help')])];
    }
}
