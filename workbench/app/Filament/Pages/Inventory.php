<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Inventory extends DemoPage
{
    protected static string|UnitEnum|null $navigationGroup = 'catalog';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
}
