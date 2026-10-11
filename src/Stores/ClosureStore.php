<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Stores;

use Closure;
use Filament\Panel;
use HoceineEl\SidebarEditor\Contracts\LayoutStore;

class ClosureStore implements LayoutStore
{
    public function __construct(
        protected Closure $getUsing,
        protected Closure $putUsing,
    ) {}

    public function get(Panel $panel): ?array
    {
        return ($this->getUsing)($panel);
    }

    public function put(Panel $panel, ?array $groups): void
    {
        ($this->putUsing)($groups, $panel);
    }
}
