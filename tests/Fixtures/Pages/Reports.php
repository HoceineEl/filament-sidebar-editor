<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures\Pages;

use Filament\Pages\Page;
use UnitEnum;

class Reports extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'insights';

    protected static ?int $navigationSort = 1;
}
