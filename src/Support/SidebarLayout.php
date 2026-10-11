<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Support;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Illuminate\Support\Collection;

/**
 * A saved sidebar: which groups and pages show, and in what order.
 *
 * Stored as `[['key', 'hidden', 'items' => [['key', 'hidden'], ...]], ...]`.
 */
final class SidebarLayout
{
    public const string MAIN_GROUP = 'main';

    /**
     * @param  array<int, array{key: string, hidden?: bool, items?: array<int, array{key: string, hidden?: bool}>}>|null  $layout
     * @param  list<string>  $pinned
     */
    public function __construct(
        private readonly ?array $layout,
        private readonly array $pinned = [],
        private readonly bool $fixedMainGroup = true,
    ) {}

    public static function itemKey(NavigationItem $item): string
    {
        return self::keyFromUrl((string) $item->getUrl());
    }

    public static function keyFromUrl(string $url): string
    {
        $path = '/'.trim((string) parse_url($url, PHP_URL_PATH), '/');
        $base = self::basePath(Filament::getCurrentOrDefaultPanel());

        $key = match (true) {
            $path === $base => '',
            str_starts_with($path, rtrim($base, '/').'/') => substr($path, strlen(rtrim($base, '/')) + 1),
            default => str($url)->before('?')->trim('/')->toString(),
        };

        return $key === '' ? 'dashboard' : $key;
    }

    /**
     * @param  array<string|int, NavigationGroup|string>  $registered
     */
    public static function groupKey(NavigationGroup $group, array $registered): string
    {
        if (blank($group->getLabel())) {
            return self::MAIN_GROUP;
        }

        foreach ($registered as $key => $candidate) {
            if ($candidate === $group || ($candidate instanceof NavigationGroup && $candidate->getLabel() === $group->getLabel())) {
                return (string) $key;
            }
        }

        return (string) str($group->getLabel())->slug();
    }

    public function isEmpty(): bool
    {
        return blank($this->layout);
    }

    public function isPinned(string $item): bool
    {
        return in_array($item, $this->pinned, true);
    }

    public function isFixed(string $group): bool
    {
        return $this->fixedMainGroup && $group === self::MAIN_GROUP;
    }

    /**
     * @param  array<NavigationGroup>  $groups
     * @param  array<string|int, NavigationGroup|string>  $registered
     * @return array<NavigationGroup>
     */
    public function apply(array $groups, array $registered): array
    {
        if ($this->isEmpty()) {
            return $groups;
        }

        $saved = collect($this->layout)->keyBy('key');
        $groupOrder = $saved->keys()->flip();

        return collect(array_values($groups))
            ->map(fn (NavigationGroup $group, int $index): array => ['key' => self::groupKey($group, $registered), 'group' => $group, 'index' => $index])
            ->sortBy(fn (array $entry): int => $this->groupPosition($groupOrder, $entry['key'], $entry['index']))
            ->map(function (array $entry) use ($saved): NavigationGroup {
                $preferences = $saved->get($entry['key'], []);
                $groupHidden = ! $this->isFixed($entry['key']) && (bool) ($preferences['hidden'] ?? false);
                $items = $this->arrangeItems(collect($entry['group']->getItems()), (array) ($preferences['items'] ?? []), $groupHidden);

                return $entry['group']->items($items->all());
            })
            ->filter(fn (NavigationGroup $group): bool => filled($group->getItems()))
            ->values()
            ->all();
    }

    /**
     * Lays the saved layout over the live catalog, so new pages appear and removed ones drop out.
     *
     * @param  array<string, array{label: string, icon: mixed, items: array<string, array{label: string, icon: mixed, children: list<array{key: string, label: string, icon: mixed}>}>}>  $catalog
     * @return list<array{key: string, hidden: bool, items: list<array{key: string, hidden: bool}>}>
     */
    public function merge(array $catalog): array
    {
        $saved = collect($this->layout ?? [])->keyBy('key');
        $groupOrder = $saved->keys()->flip();

        return collect($catalog)
            ->keys()
            ->sortBy(fn (string $key, int $index): int => $this->groupPosition($groupOrder, $key, $index))
            ->map(function (string $key) use ($saved, $catalog): array {
                $group = $saved->get($key, []);
                $savedItems = collect($group['items'] ?? [])->keyBy('key');
                $itemOrder = $savedItems->keys()->flip();
                $rows = collect($catalog[$key]['items'])
                    ->flatMap(fn (array $item, string $itemKey): array => [$itemKey, ...array_column($item['children'], 'key')])
                    ->values();
                $hasPinned = $rows->contains(fn (string $itemKey): bool => $this->isPinned($itemKey));

                return [
                    'key' => $key,
                    'hidden' => ! $this->isFixed($key) && ! $hasPinned && (bool) ($group['hidden'] ?? false),
                    'items' => $rows
                        ->sortBy(fn (string $itemKey, int $index): int => $this->position($itemOrder, $itemKey, $index))
                        ->map(fn (string $itemKey): array => ['key' => $itemKey, 'hidden' => $this->hides($savedItems, $itemKey)])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, NavigationItem>  $items
     * @param  array<int, array{key: string, hidden?: bool}>  $saved
     * @return Collection<int, NavigationItem>
     */
    private function arrangeItems(Collection $items, array $saved, bool $groupHidden): Collection
    {
        $preferences = collect($saved)->keyBy('key');
        $order = $preferences->keys()->flip();

        return $items
            ->values()
            ->map(fn (NavigationItem $item, int $index): array => ['key' => self::itemKey($item), 'item' => $item, 'index' => $index])
            ->reject(fn (array $entry): bool => $this->hides($preferences, $entry['key'], $groupHidden))
            ->sortBy(fn (array $entry): int => $this->position($order, $entry['key'], $entry['index']))
            ->map(function (array $entry) use ($preferences): NavigationItem {
                $children = collect($entry['item']->getChildItems())
                    ->reject(fn (NavigationItem $child): bool => $this->hides($preferences, self::itemKey($child)))
                    ->values()
                    ->all();

                return $entry['item']->childItems($children);
            })
            ->values();
    }

    /**
     * @param  Collection<array-key, array{key: string, hidden?: bool}>  $preferences
     */
    private function hides(Collection $preferences, string $key, bool $groupHidden = false): bool
    {
        return ! $this->isPinned($key) && ($groupHidden || (bool) ($preferences->get($key)['hidden'] ?? false));
    }

    /**
     * @param  Collection<array-key, int>  $order
     */
    private function position(Collection $order, string $key, int $index): int
    {
        return (int) ($order[$key] ?? 1000 + $index);
    }

    /**
     * @param  Collection<array-key, int>  $order
     */
    private function groupPosition(Collection $order, string $key, int $index): int
    {
        return $this->isFixed($key) ? -1 : $this->position($order, $key, $index);
    }

    private static function basePath(Panel $panel): string
    {
        $segments = [$panel->getPath()];
        $tenant = Filament::getTenant();

        if ($tenant !== null && $panel->hasTenancy() && blank($panel->getTenantDomain())) {
            $slugAttribute = $panel->getTenantSlugAttribute();

            $segments[] = $panel->getTenantRoutePrefix();
            $segments[] = (string) (filled($slugAttribute) ? $tenant->getAttributeValue($slugAttribute) : $tenant->getRouteKey());
        }

        return '/'.collect($segments)->map(fn (?string $segment): string => trim((string) $segment, '/'))->filter()->implode('/');
    }
}
