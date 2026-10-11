<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use HoceineEl\SidebarEditor\Navigation\LayoutNavigationManager;
use HoceineEl\SidebarEditor\SidebarEditorPlugin;
use HoceineEl\SidebarEditor\Support\SidebarLayout;
use Illuminate\Support\Collection;
use UnitEnum;

class SidebarEditor extends Page
{
    protected string $view = 'sidebar-editor::pages.sidebar-editor';

    /** @var list<array{key: string, hidden: bool, items: list<array{key: string, hidden: bool}>}> */
    public array $sidebar = [];

    /** @var list<array{key: string, hidden: bool, items: list<array{key: string, hidden: bool}>}> */
    public array $saved = [];

    public static function getSlug(?Panel $panel = null): string
    {
        return SidebarEditorPlugin::current($panel)?->getSlug() ?? 'sidebar';
    }

    public static function getNavigationLabel(): string
    {
        return static::plugin()->getNavigationLabel();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return static::plugin()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return static::plugin()->getNavigationSort();
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return static::plugin()->getNavigationIcon();
    }

    public static function canAccess(): bool
    {
        return SidebarEditorPlugin::current()?->isAuthorized() ?? false;
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function getSubheading(): string
    {
        return __('sidebar-editor::sidebar-editor.subheading');
    }

    public function mount(): void
    {
        $this->sidebar = $this->saved = $this->layout()->merge($this->catalog());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label(__('sidebar-editor::sidebar-editor.actions.reset'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('sidebar-editor::sidebar-editor.actions.reset_hint'))
                ->action(function (): void {
                    static::plugin()->getStore()->put(Filament::getCurrentOrDefaultPanel(), null);
                    Notification::make()->success()->title(__('sidebar-editor::sidebar-editor.notifications.reset'))->send();
                    $this->redirect(static::getUrl());
                }),
            Action::make('discard')
                ->label(__('sidebar-editor::sidebar-editor.actions.discard'))
                ->color('gray')
                ->visible(fn (): bool => $this->isDirty())
                ->action(fn () => $this->sidebar = $this->saved),
            Action::make('save')
                ->label(__('sidebar-editor::sidebar-editor.actions.save'))
                ->icon(Heroicon::OutlinedCheck)
                ->disabled(fn (): bool => ! $this->isDirty())
                ->keyBindings(['mod+s'])
                ->action(function (): void {
                    static::plugin()->getStore()->put(Filament::getCurrentOrDefaultPanel(), $this->sidebar);
                    $this->saved = $this->sidebar;
                    Notification::make()->success()->title(__('sidebar-editor::sidebar-editor.notifications.saved'))->send();
                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function isDirty(): bool
    {
        return $this->sidebar !== $this->saved;
    }

    public function toggleGroup(string $group): void
    {
        if (! $this->canHideGroup($group)) {
            return;
        }

        $this->sidebar = collect($this->sidebar)
            ->map(fn (array $entry): array => $entry['key'] === $group ? [...$entry, 'hidden' => ! $entry['hidden']] : $entry)
            ->all();
    }

    public function toggleItem(string $group, string $item): void
    {
        if ($this->layout()->isPinned($item)) {
            return;
        }

        $this->sidebar = collect($this->sidebar)
            ->map(fn (array $entry): array => $entry['key'] !== $group ? $entry : [
                ...$entry,
                'items' => collect($entry['items'])
                    ->map(fn (array $row): array => $row['key'] === $item ? [...$row, 'hidden' => ! $row['hidden']] : $row)
                    ->all(),
            ])
            ->all();
    }

    public function showEverything(): void
    {
        $this->sidebar = collect($this->sidebar)
            ->map(fn (array $entry): array => [
                ...$entry,
                'hidden' => false,
                'items' => collect($entry['items'])->map(fn (array $row): array => [...$row, 'hidden' => false])->all(),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $keys
     */
    public function reorderGroups(array $keys): void
    {
        [$fixed, $movable] = collect($this->sidebar)->partition(fn (array $entry): bool => $this->layout()->isFixed($entry['key']));

        $this->sidebar = $fixed->concat($this->sortByKeys($movable, $keys))->values()->all();
    }

    /**
     * @param  list<string>  $keys
     */
    public function reorderItems(string $group, array $keys): void
    {
        $this->sidebar = collect($this->sidebar)
            ->map(fn (array $entry): array => $entry['key'] !== $group ? $entry : [...$entry, 'items' => $this->sortByKeys(collect($entry['items']), $keys)->all()])
            ->all();
    }

    public function canHideGroup(string $group): bool
    {
        $layout = $this->layout();

        $items = collect($this->sidebar)->firstWhere('key', $group)['items'] ?? [];

        return ! $layout->isFixed($group) && ! collect($items)->contains(fn (array $row): bool => $layout->isPinned($row['key']));
    }

    public function layout(): SidebarLayout
    {
        return once(fn (): SidebarLayout => static::plugin()->layout(Filament::getCurrentOrDefaultPanel()));
    }

    /**
     * Labels, icons and child pages for everything the sidebar can show, keyed by group then item.
     *
     * @return array<string, array{label: string, icon: mixed, items: array<string, array{label: string, icon: mixed, children: list<array{key: string, label: string, icon: mixed}>}>}>
     */
    public function catalog(): array
    {
        return once(function (): array {
            $panel = Filament::getCurrentOrDefaultPanel();
            $groups = LayoutNavigationManager::withoutLayout(fn (): array => $panel->getNavigation());

            return collect($groups)
                ->mapWithKeys(function (NavigationGroup $group) use ($panel): array {
                    $items = collect($group->getItems())->mapWithKeys(fn (NavigationItem $item): array => [
                        SidebarLayout::itemKey($item) => [
                            'label' => $item->getLabel(),
                            'icon' => $item->getIcon(),
                            'children' => collect($item->getChildItems())->map(fn (NavigationItem $child): array => [
                                'key' => SidebarLayout::itemKey($child),
                                'label' => $child->getLabel(),
                                'icon' => $child->getIcon(),
                            ])->values()->all(),
                        ],
                    ]);

                    $key = SidebarLayout::groupKey($group, $panel->getNavigationGroups());

                    return [$key => [
                        'label' => $key === SidebarLayout::MAIN_GROUP ? __('sidebar-editor::sidebar-editor.main_group') : $group->getLabel(),
                        'icon' => $group->getIcon(),
                        'items' => $items->all(),
                    ]];
                })
                ->all();
        });
    }

    /**
     * @template TEntry of array{key: string}
     *
     * @param  Collection<int, TEntry>  $entries
     * @param  list<string>  $keys
     * @return Collection<int, TEntry>
     */
    private function sortByKeys(Collection $entries, array $keys): Collection
    {
        $order = array_flip($keys);

        return $entries->values()->sortBy(fn (array $entry, int $index): int => $order[$entry['key']] ?? 1000 + $index)->values();
    }

    protected static function plugin(): SidebarEditorPlugin
    {
        return SidebarEditorPlugin::current() ?? SidebarEditorPlugin::make();
    }
}
