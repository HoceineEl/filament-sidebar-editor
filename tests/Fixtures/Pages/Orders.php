<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures\Pages;

use Filament\Pages\Page;
use UnitEnum;

class Orders extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'sales';

    protected static ?int $navigationSort = 1;
}
