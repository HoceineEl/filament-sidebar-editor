<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Contracts;

use Filament\Panel;

/**
 * @phpstan-type Layout list<array{key: string, hidden: bool, items: list<array{key: string, hidden: bool}>}>
 */
interface LayoutStore
{
    /**
     * @return Layout|null
     */
    public function get(Panel $panel): ?array;

    /**
     * @param  Layout|null  $groups
     */
    public function put(Panel $panel, ?array $groups): void;
}
