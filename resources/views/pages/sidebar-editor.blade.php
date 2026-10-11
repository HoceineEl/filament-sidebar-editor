@php
    use Filament\Support\Facades\FilamentAsset;
    use HoceineEl\SidebarEditor\Support\SidebarLayout;

    $catalog = $this->catalog();
    $layout = $this->layout();
    $topLevel = collect($sidebar)
        ->filter(fn ($group) => isset($catalog[$group['key']]))
        ->mapWithKeys(function ($group) use ($catalog) {
            $items = $catalog[$group['key']]['items'];
            $childKeys = collect($items)->flatMap(fn ($item) => array_column($item['children'], 'key'))->all();

            return [$group['key'] => collect($group['items'])->reject(fn ($row) => in_array($row['key'], $childKeys, true) || ! isset($items[$row['key']]))];
        });
@endphp

<x-filament-panels::page>
    <div
        class="sbe-editor"
        x-load-css="[@js(FilamentAsset::getStyleHref('sidebar-editor', 'hoceineel/filament-sidebar-editor'))]"
    >
        <div class="sbe-main">
            <div class="sbe-toolbar">
                <p class="sbe-hint">
                    <x-filament::icon icon="heroicon-m-information-circle" class="sbe-hint-icon" />
                    {{ __('sidebar-editor::sidebar-editor.hint') }}
                </p>
                <x-filament::button size="sm" color="gray" icon="heroicon-m-eye" wire:click="showEverything">
                    {{ __('sidebar-editor::sidebar-editor.actions.show_all') }}
                </x-filament::button>
            </div>

            @if ($this->isDirty())
                <div class="sbe-dirty" role="status">
                    <span class="sbe-dirty-dot" aria-hidden="true"></span>
                    {{ __('sidebar-editor::sidebar-editor.unsaved') }}
                </div>
            @endif

            <div
                class="sbe-groups"
                x-data
                x-init="
                    if (! window.Sortable) return
                    window.Sortable.create($el, {
                        draggable: '[data-group]:not([data-fixed])',
                        handle: '[data-group-handle]',
                        animation: 150,
                        ghostClass: 'sbe-ghost',
                        onEnd: () => $wire.reorderGroups([...$el.querySelectorAll(':scope > [data-group]:not([data-fixed])')].map((node) => node.dataset.group)),
                    })
                "
            >
                @foreach ($sidebar as $group)
                    @continue (! $topLevel->has($group['key']))
                    @php
                        $meta = $catalog[$group['key']];
                        $rows = $topLevel[$group['key']];
                        $states = collect($group['items'])->pluck('hidden', 'key');
                        $visibleCount = $rows->where('hidden', false)->count();
                        $isFixed = $layout->isFixed($group['key']);
                    @endphp

                    <section
                        wire:key="group-{{ $group['key'] }}"
                        data-group="{{ $group['key'] }}"
                        @if ($isFixed) data-fixed @endif
                        @class(['sbe-group', 'is-hidden' => $group['hidden']])
                        aria-labelledby="sbe-group-{{ $group['key'] }}"
                    >
                        <header class="sbe-group-header">
                            @unless ($isFixed)
                                <button type="button" data-group-handle class="sbe-handle" aria-label="{{ __('sidebar-editor::sidebar-editor.drag_group', ['group' => $meta['label']]) }}">
                                    <x-filament::icon icon="heroicon-m-bars-2" class="sbe-icon" />
                                </button>
                            @endunless

                            <div class="sbe-group-title">
                                <h3 id="sbe-group-{{ $group['key'] }}">{{ $meta['label'] }}</h3>
                                <p>{{ trans_choice('sidebar-editor::sidebar-editor.shown_count', $rows->count(), ['shown' => $visibleCount, 'total' => $rows->count()]) }}</p>
                            </div>

                            @if ($this->canHideGroup($group['key']))
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked="{{ $group['hidden'] ? 'false' : 'true' }}"
                                    wire:click="toggleGroup(@js($group['key']))"
                                    @class(['sbe-visibility', 'is-off' => $group['hidden']])
                                >
                                    <x-filament::icon :icon="$group['hidden'] ? 'heroicon-m-eye-slash' : 'heroicon-m-eye'" class="sbe-icon" />
                                    <span>{{ $group['hidden'] ? __('sidebar-editor::sidebar-editor.group_hidden') : __('sidebar-editor::sidebar-editor.group_shown') }}</span>
                                </button>
                            @elseif (! $isFixed)
                                <span class="sbe-pinned">
                                    <x-filament::icon icon="heroicon-m-lock-closed" class="sbe-icon-sm" />
                                    {{ __('sidebar-editor::sidebar-editor.always_shown') }}
                                </span>
                            @endif
                        </header>

                        <ul
                            class="sbe-items"
                            x-data
                            x-init="
                                if (! window.Sortable) return
                                window.Sortable.create($el, {
                                    draggable: '[data-item]',
                                    handle: '[data-item-handle]',
                                    animation: 150,
                                    ghostClass: 'sbe-ghost',
                                    onEnd: () => $wire.reorderItems(@js($group['key']), [...$el.querySelectorAll(':scope > [data-item]')].map((node) => node.dataset.item)),
                                })
                            "
                        >
                            @foreach ($rows as $row)
                                @php
                                    $item = $meta['items'][$row['key']];
                                    $isOff = $row['hidden'] || $group['hidden'];
                                @endphp

                                <li wire:key="item-{{ $group['key'] }}-{{ $row['key'] }}" data-item="{{ $row['key'] }}" @class(['sbe-item', 'is-hidden' => $isOff])>
                                    <div class="sbe-item-row">
                                        <button type="button" data-item-handle class="sbe-handle" aria-label="{{ __('sidebar-editor::sidebar-editor.drag_item', ['item' => $item['label']]) }}">
                                            <x-filament::icon icon="heroicon-m-bars-2" class="sbe-icon" />
                                        </button>
                                        @if ($item['icon'])
                                            <x-filament::icon :icon="$item['icon']" class="sbe-item-icon" />
                                        @endif
                                        <span class="sbe-item-label">{{ $item['label'] }}</span>

                                        @if ($layout->isPinned($row['key']))
                                            <span class="sbe-pinned">
                                                <x-filament::icon icon="heroicon-m-lock-closed" class="sbe-icon-sm" />
                                                {{ __('sidebar-editor::sidebar-editor.always_shown') }}
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                role="switch"
                                                aria-checked="{{ $row['hidden'] ? 'false' : 'true' }}"
                                                aria-label="{{ $item['label'] }}"
                                                wire:click="toggleItem(@js($group['key']), @js($row['key']))"
                                                @class(['sbe-visibility', 'is-off' => $row['hidden']])
                                            >
                                                <x-filament::icon :icon="$row['hidden'] ? 'heroicon-m-eye-slash' : 'heroicon-m-eye'" class="sbe-icon" />
                                                <span>{{ $row['hidden'] ? __('sidebar-editor::sidebar-editor.hidden') : __('sidebar-editor::sidebar-editor.shown') }}</span>
                                            </button>
                                        @endif
                                    </div>

                                    @if (filled($item['children']))
                                        <ul class="sbe-children">
                                            @foreach ($item['children'] as $child)
                                                @php $childHidden = (bool) ($states[$child['key']] ?? false); @endphp
                                                <li wire:key="child-{{ $child['key'] }}" @class(['sbe-child', 'is-hidden' => $childHidden || $isOff])>
                                                    <span class="sbe-item-label">{{ $child['label'] }}</span>
                                                    <button
                                                        type="button"
                                                        role="switch"
                                                        aria-checked="{{ $childHidden ? 'false' : 'true' }}"
                                                        aria-label="{{ $child['label'] }}"
                                                        wire:click="toggleItem(@js($group['key']), @js($child['key']))"
                                                        @class(['sbe-visibility', 'is-off' => $childHidden])
                                                    >
                                                        <x-filament::icon :icon="$childHidden ? 'heroicon-m-eye-slash' : 'heroicon-m-eye'" class="sbe-icon" />
                                                        <span>{{ $childHidden ? __('sidebar-editor::sidebar-editor.hidden') : __('sidebar-editor::sidebar-editor.shown') }}</span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        </div>

        <aside class="sbe-preview" aria-label="{{ __('sidebar-editor::sidebar-editor.preview') }}">
            <p class="sbe-preview-title">{{ __('sidebar-editor::sidebar-editor.preview') }}</p>
            <nav class="sbe-preview-nav">
                @foreach ($sidebar as $group)
                    @continue (! $topLevel->has($group['key']) || $group['hidden'])
                    @php
                        $meta = $catalog[$group['key']];
                        $shown = $topLevel[$group['key']]->where('hidden', false);
                    @endphp
                    @continue ($shown->isEmpty())

                    <div wire:key="preview-{{ $group['key'] }}" class="sbe-preview-group">
                        @if ($group['key'] !== SidebarLayout::MAIN_GROUP)
                            <p class="sbe-preview-group-label">{{ $meta['label'] }}</p>
                        @endif
                        @foreach ($shown as $row)
                            @php $item = $meta['items'][$row['key']]; @endphp
                            <span wire:key="preview-{{ $group['key'] }}-{{ $row['key'] }}" class="sbe-preview-item">
                                @if ($item['icon'])
                                    <x-filament::icon :icon="$item['icon']" class="sbe-icon" />
                                @endif
                                <span class="sbe-truncate">{{ $item['label'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endforeach
            </nav>
        </aside>
    </div>
</x-filament-panels::page>
