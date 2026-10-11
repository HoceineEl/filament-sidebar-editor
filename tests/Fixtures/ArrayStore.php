<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures;

use Filament\Panel;
use HoceineEl\SidebarEditor\Contracts\LayoutStore;

class ArrayStore implements LayoutStore
{
    /** @var array<string, array<int, array<string, mixed>>|null> */
    public static array $layouts = [];

    public function get(Panel $panel): ?array
    {
        return static::$layouts[$panel->getId()] ?? null;
    }

    public function put(Panel $panel, ?array $groups): void
    {
        static::$layouts[$panel->getId()] = $groups;
    }
}
