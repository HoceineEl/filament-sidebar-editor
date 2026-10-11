<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Navigation;

use Closure;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use HoceineEl\SidebarEditor\SidebarEditorPlugin;
use UnitEnum;

/**
 * Wraps whichever navigation manager is bound and lays the saved sidebar over its result.
 */
class LayoutNavigationManager extends NavigationManager
{
    private static bool $bypassed = false;

    public function __construct(protected NavigationManager $inner)
    {
        $this->panel = Filament::getCurrentOrDefaultPanel();
    }

    /**
     * @return array<NavigationGroup>
     */
    public function get(): array
    {
        $groups = $this->inner->get();
        $plugin = SidebarEditorPlugin::current($this->panel);

        if (self::$bypassed || $plugin === null) {
            return $groups;
        }

        return $plugin->layout($this->panel)->apply($groups, $this->inner->getNavigationGroups());
    }

    public function mountNavigation(): void
    {
        $this->inner->mountNavigation();
    }

    /**
     * @param  array<string|int, NavigationGroup|string>|class-string<UnitEnum>  $groups
     */
    public function navigationGroups(array|string $groups): static
    {
        $this->inner->navigationGroups($groups);

        return $this;
    }

    /**
     * @param  array<NavigationItem>  $items
     */
    public function navigationItems(array $items): static
    {
        $this->inner->navigationItems($items);

        return $this;
    }

    /**
     * @return array<string|int, NavigationGroup|string>
     */
    public function getNavigationGroups(): array
    {
        return $this->inner->getNavigationGroups();
    }

    /**
     * @return array<NavigationItem>
     */
    public function getNavigationItems(): array
    {
        return $this->inner->getNavigationItems();
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutLayout(Closure $callback): mixed
    {
        $previous = self::$bypassed;
        self::$bypassed = true;

        try {
            return $callback();
        } finally {
            self::$bypassed = $previous;
        }
    }
}
