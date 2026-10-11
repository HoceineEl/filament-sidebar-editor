<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures\Pages;

use Filament\Pages\Page;
use UnitEnum;

class Categories extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'inventory';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationParentItem = 'Products';
}
