<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor;

use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Icons\Heroicon;
use HoceineEl\SidebarEditor\Contracts\LayoutStore;
use HoceineEl\SidebarEditor\Enums\LayoutScope;
use HoceineEl\SidebarEditor\Pages\SidebarEditor;
use HoceineEl\SidebarEditor\Stores\ClosureStore;
use HoceineEl\SidebarEditor\Stores\GlobalStore;
use HoceineEl\SidebarEditor\Stores\TenantStore;
use HoceineEl\SidebarEditor\Stores\UserStore;
use HoceineEl\SidebarEditor\Support\SidebarLayout;
use UnitEnum;

class SidebarEditorPlugin implements Plugin
{
    use EvaluatesClosures;

    protected LayoutScope $scope = LayoutScope::Global;

    protected string $tenantColumn = 'navigation';

    /** @var LayoutStore|class-string<LayoutStore>|null */
    protected LayoutStore|string|null $customStore = null;

    protected bool|Closure $authorized = true;

    protected string|Closure $slug = 'sidebar';

    protected string|UnitEnum|Closure|null $navigationGroup = null;

    protected int|Closure|null $navigationSort = null;

    protected string|BackedEnum|Closure|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected string|Closure|null $navigationLabel = null;

    /** @var list<string>|Closure */
    protected array|Closure $pinnedItems = [];

    protected bool|Closure $fixedMainGroup = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public static function current(?Panel $panel = null): ?static
    {
        $panel ??= Filament::getCurrentPanel();
        $id = app(static::class)->getId();

        if (! $panel?->hasPlugin($id)) {
            return null;
        }

        $plugin = $panel->getPlugin($id);

        return $plugin instanceof static ? $plugin : null;
    }

    public function getId(): string
    {
        return 'sidebar-editor';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([SidebarEditor::class]);
    }

    public function boot(Panel $panel): void {}

    public function storeOnTenant(string $column = 'navigation'): static
    {
        $this->scope = LayoutScope::Tenant;
        $this->tenantColumn = $column;

        return $this;
    }

    public function storePerUser(): static
    {
        $this->scope = LayoutScope::User;

        return $this;
    }

    public function storeGlobally(): static
    {
        $this->scope = LayoutScope::Global;

        return $this;
    }

    /**
     * @param  LayoutStore|class-string<LayoutStore>  $store
     */
    public function store(LayoutStore|string $store): static
    {
        $this->scope = LayoutScope::Custom;
        $this->customStore = $store;

        return $this;
    }

    public function storeUsing(Closure $get, Closure $put): static
    {
        return $this->store(new ClosureStore($get, $put));
    }

    public function authorize(bool|Closure $condition = true): static
    {
        $this->authorized = $condition;

        return $this;
    }

    public function slug(string|Closure $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function navigationGroup(string|UnitEnum|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationSort(int|Closure|null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function navigationIcon(string|BackedEnum|Closure|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function navigationLabel(string|Closure|null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $items
     */
    public function pinnedItems(array|Closure $items): static
    {
        $this->pinnedItems = $items;

        return $this;
    }

    public function fixedMainGroup(bool|Closure $condition = true): static
    {
        $this->fixedMainGroup = $condition;

        return $this;
    }

    public function getScope(): LayoutScope
    {
        return $this->scope;
    }

    public function getStore(): LayoutStore
    {
        return match ($this->scope) {
            LayoutScope::Tenant => new TenantStore($this->tenantColumn),
            LayoutScope::User => app(UserStore::class),
            LayoutScope::Global => app(GlobalStore::class),
            LayoutScope::Custom => is_string($this->customStore) ? app($this->customStore) : $this->customStore,
        };
    }

    public function layout(?Panel $panel = null): SidebarLayout
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        return new SidebarLayout($this->getStore()->get($panel), $this->getPinnedItems(), $this->hasFixedMainGroup());
    }

    public function isAuthorized(): bool
    {
        return (bool) $this->evaluate($this->authorized);
    }

    public function getSlug(): string
    {
        return trim((string) $this->evaluate($this->slug), '/');
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        return $this->evaluate($this->navigationGroup);
    }

    public function getNavigationSort(): ?int
    {
        return $this->evaluate($this->navigationSort);
    }

    public function getNavigationIcon(): string|BackedEnum|null
    {
        return $this->evaluate($this->navigationIcon);
    }

    public function getNavigationLabel(): string
    {
        return $this->evaluate($this->navigationLabel) ?? __('sidebar-editor::sidebar-editor.title');
    }

    /**
     * @return list<string>
     */
    public function getPinnedItems(): array
    {
        return array_values(array_unique([...(array) $this->evaluate($this->pinnedItems), $this->getSlug()]));
    }

    public function hasFixedMainGroup(): bool
    {
        return (bool) $this->evaluate($this->fixedMainGroup);
    }
}
