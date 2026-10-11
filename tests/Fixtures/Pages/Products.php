<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Products extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'inventory';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
}
