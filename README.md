<p class="filament-hidden"><img src="https://raw.githubusercontent.com/HoceineEl/filament-sidebar-editor/main/docs/images/cover.jpg" alt="Sidebar Editor"></p>

# Sidebar Editor for Filament

A "Sidebar" page for Filament 5 panels where people hide, show and reorder navigation groups and pages. Drag a group or a page by its handle, switch pages and child pages on or off, and watch the live preview update before you save.

The layout can belong to the current tenant, to each user, or to the whole panel, or you can store it yourself. New pages and resources show up on their own, and the editor page can never be hidden, so nobody locks themselves out.

![The sidebar editor with a live preview](https://raw.githubusercontent.com/HoceineEl/filament-sidebar-editor/main/docs/images/editor.png)

## Requirements

| Package | Version |
| --- | --- |
| PHP | 8.3+ |
| Laravel | 12 or 13 |
| Filament | 5 |

## Installation

```bash
composer require hoceineel/filament-sidebar-editor
php artisan filament:assets
```

Run `filament:assets` again after each update. If your `composer.json` runs `filament:upgrade` on `post-autoload-dump`, that already happens. No custom theme is needed; the page loads its own stylesheet.

The user and global drivers keep layouts in the package's table. Publish and run its migration if you use either:

```bash
php artisan vendor:publish --tag=sidebar-editor-migrations
php artisan migrate
```

Optional publishes:

```bash
php artisan vendor:publish --tag=sidebar-editor-config
php artisan vendor:publish --tag=sidebar-editor-translations
php artisan vendor:publish --tag=sidebar-editor-views
```

## Quick start

Register the plugin on each panel that should have the editor:

```php
use HoceineEl\SidebarEditor\SidebarEditorPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(SidebarEditorPlugin::make());
}
```

Open `/admin/sidebar` (or `/app/{tenant}/sidebar` in a tenant panel), hide or move something, and save. The real sidebar changes on the next page load. Panels that do not register the plugin keep Filament's navigation as it is.

## Configuration

Every option is a fluent method on the plugin. Values marked as closures are evaluated when needed.

| Method | Default | What it does |
| --- | --- | --- |
| `storeOnTenant(string $column = 'navigation')` | | Keep the layout on a column of the current tenant. |
| `storePerUser()` | | One layout per user and panel, in the package's table. |
| `storeGlobally()` | yes | One layout for the whole panel, in the package's table. |
| `store(LayoutStore\|string)` | | Your own store, as an instance or a class name resolved from the container. |
| `storeUsing(Closure $get, Closure $put)` | | Your own store as two closures. |
| `authorize(bool\|Closure)` | `true` | Who may open the editor. When it returns `false`, the page is hidden from the sidebar and returns 403. |
| `slug(string\|Closure)` | `'sidebar'` | The page's URL segment. The page is always pinned under this key. |
| `navigationGroup(string\|UnitEnum\|Closure\|null)` | `null` | Group the editor page sits in. |
| `navigationSort(int\|Closure\|null)` | `null` | Sort of the editor page inside its group. |
| `navigationIcon(string\|BackedEnum\|Closure\|null)` | `Heroicon::OutlinedBars3BottomLeft` | Icon of the editor page. |
| `navigationLabel(string\|Closure\|null)` | "Sidebar" (translated) | Label and title of the editor page. |
| `pinnedItems(array\|Closure)` | `[]` | Page keys that can never be hidden. The editor page is always added. |
| `fixedMainGroup(bool\|Closure)` | `true` | Keep the ungrouped pages (dashboard and friends) at the top. When `false`, they can be moved and hidden like any group. |

```php
use App\Models\User;
use HoceineEl\SidebarEditor\SidebarEditorPlugin;

SidebarEditorPlugin::make()
    ->storeOnTenant('navigation')
    ->authorize(fn (): bool => auth()->user()?->can('manage-sidebar') ?? false)
    ->slug('menu')
    ->navigationGroup('settings')
    ->navigationSort(45)
    ->pinnedItems(['dashboard', 'billing'])
    ->fixedMainGroup();
```

Each panel has its own registration and options. `SidebarEditorPlugin::get()` returns the plugin on the current panel and `SidebarEditorPlugin::current(?Panel $panel)` returns it or `null`.

### Keys

Pages are identified by their URL path inside the panel (and inside the tenant), so `/app/acme/products/categories` is `products/categories` and the panel home is `dashboard`. Groups use the key you registered them under with `navigationGroups()`, or the slug of their label. Use these keys in `pinnedItems()`.

### Config file

`config/sidebar-editor.php` has one option, the table used by the user and global drivers:

```php
return [
    'table' => 'sidebar_editor_layouts',
];
```

## Storage drivers

A layout is a list of groups, each with its pages:

```php
[
    ['key' => 'sales', 'hidden' => false, 'items' => [
        ['key' => 'orders', 'hidden' => false],
        ['key' => 'coupons', 'hidden' => true],
    ]],
    ['key' => 'reports', 'hidden' => true, 'items' => []],
]
```

### Tenant

`storeOnTenant('navigation')` writes `{"groups": [...]}` to that column of `Filament::getTenant()`. Add a nullable JSON column and, ideally, an `array` cast:

```php
Schema::table('teams', fn (Blueprint $table) => $table->json('navigation')->nullable());

protected function casts(): array
{
    return ['navigation' => 'array'];
}
```

Columns without a cast are read and written as JSON strings. Each tenant only ever sees its own layout.

### User

`storePerUser()` keeps one row per user (from the panel's auth guard) and panel in the package's table. In a tenant panel, a user's layout follows them across tenants. Guests get the default sidebar.

### Global

`storeGlobally()` keeps one row per panel. Everyone sees the same sidebar. This is the default.

### Custom

Implement `HoceineEl\SidebarEditor\Contracts\LayoutStore`:

```php
use Filament\Panel;
use HoceineEl\SidebarEditor\Contracts\LayoutStore;

class SettingsStore implements LayoutStore
{
    public function get(Panel $panel): ?array
    {
        return settings("sidebar.{$panel->getId()}");
    }

    public function put(Panel $panel, ?array $groups): void
    {
        settings()->set("sidebar.{$panel->getId()}", $groups);
    }
}

SidebarEditorPlugin::make()->store(SettingsStore::class);
```

Or with two closures. `put` receives `null` when someone restores the default:

```php
SidebarEditorPlugin::make()->storeUsing(
    get: fn (Panel $panel): ?array => cache()->get("sidebar.{$panel->getId()}"),
    put: fn (?array $groups, Panel $panel) => cache()->forever("sidebar.{$panel->getId()}", $groups),
);
```

## What the editor does

- Hide or show each group, page and child page (such as Products › Categories). Counts show how many pages of a group are visible.
- Drag groups and pages by their handles. Sorting uses the SortableJS copy Filament already ships, so there is no extra JavaScript.
- A preview on the side shows the sidebar as it will look.
- Unsaved changes are flagged. Save with the button or `⌘S` / `Ctrl+S`, or discard them.
- "Show everything" switches every group and page back on; "Restore default" deletes the saved layout.
- Pinned pages show a lock instead of a switch, and so does any group that holds one.

Hiding a page only removes it from the sidebar. It does not change who can open it: keep using policies and `canAccess()` for that.

## How it works

The package wraps Filament's `NavigationManager` with `app()->extend()`. The wrapper calls the bound manager, then lays the saved layout over its result when the current panel has the plugin. Saved layouts are merged with the live navigation, so pages added later appear at the end of their group and pages that no longer exist are ignored.

## Translations

English, French and Arabic ship with the package. Publish them with `--tag=sidebar-editor-translations` to change the wording or add a language. Keys live in `sidebar-editor::sidebar-editor`.

## Dark mode and RTL

![The editor in dark mode](https://raw.githubusercontent.com/HoceineEl/filament-sidebar-editor/main/docs/images/dark.png)

![The editor in Arabic, right to left](https://raw.githubusercontent.com/HoceineEl/filament-sidebar-editor/main/docs/images/rtl.png)

## Theming

The stylesheet uses classes prefixed with `sbe-` and Filament's color variables (`--gray-*`, `--primary-*`, `--warning-*`), so it follows your panel's colors, dark mode and text direction. Override the `--sbe-*` custom properties on `.sbe-editor` to adjust surfaces and borders.

## FAQ

**Another package also replaces the `NavigationManager`. Will they clash?** Usually not. Laravel keeps `extend()` callbacks when a binding is replaced, so if another package or your app calls `app()->bind()` or `app()->scoped()` for `NavigationManager`, the sidebar layout still wraps their manager and their changes come first. Another package that uses `extend()` too is wrapped in the order the providers boot. The only setup that bypasses the editor is code that creates `new NavigationManager` itself instead of resolving it from the container, as Filament does internally to find a panel's home URL.

**Does it work with a custom navigation builder (`$panel->navigation(fn (NavigationBuilder $builder) => ...)`)?** Yes. The builder's groups are what the layout is applied to, but keys still come from URLs, so items without a URL cannot be told apart.

**A page I hid still opens from its URL.** That is expected. The editor only changes the sidebar.

**The page has no styles.** Run `php artisan filament:assets`. A 404 for `sidebar-editor.css` in the console means the asset was not published.

**Can someone hide the editor itself?** No. Its key is always pinned, the group holding it cannot be hidden, and a hidden group in a saved layout still keeps its pinned pages.

## Testing

```bash
composer test               # Pest
vendor/bin/pest --parallel  # faster
composer analyse            # PHPStan
composer format             # Pint
```

The Playwright suite runs against the Testbench workbench in `workbench/`:

```bash
npm ci && npm run build
npx playwright install chromium
npm run test:e2e
```

`composer serve` starts the workbench at `/admin/sidebar`; add `?locale=ar` or `?locale=fr` to switch language. `SE_SCREENSHOTS=1 npm run test:e2e -- screenshots` retakes the README images.

To test the editor in your app, call its Livewire methods and actions:

```php
use HoceineEl\SidebarEditor\Pages\SidebarEditor;
use Livewire\Livewire;

it('hides coupons', function (): void {
    Livewire::test(SidebarEditor::class)
        ->call('toggleItem', 'sales', 'coupons')
        ->callAction('save');

    expect($team->fresh()->navigation['groups'])->not->toBeEmpty();
});
```

The page also exposes `toggleGroup()`, `showEverything()`, `reorderGroups()` and `reorderItems()`, and the `reset` and `discard` actions.

## More from Hoceine

Paid packages for Filament 4 and 5 by the same author:

<table>
<tr>
<td width="33%" valign="top"><a href="https://filamentcraft.dev?ref=sidebar-editor"><img src="https://raw.githubusercontent.com/HoceineEl/filament-sidebar-editor/main/docs/images/more/filamentcraft.jpg" alt="FilamentCraft"></a><br><b><a href="https://filamentcraft.dev?ref=sidebar-editor">FilamentCraft</a></b><br>A visual website builder inside your Filament panel: drag sections onto a live canvas, edit against a preview, publish.</td>
<td width="33%" valign="top"><a href="https://media-library.hoceine.com?ref=sidebar-editor"><img src="https://raw.githubusercontent.com/HoceineEl/filament-sidebar-editor/main/docs/images/more/media-library-pro.jpg" alt="Media Library Pro"></a><br><b><a href="https://media-library.hoceine.com?ref=sidebar-editor">Media Library Pro</a></b><br>One shared media library: folders, tags, focal points, chunked uploads, duplicate detection and usage tracking.</td>
<td width="33%" valign="top"><a href="https://scheduler-pro.hoceine.com?ref=sidebar-editor"><img src="https://raw.githubusercontent.com/HoceineEl/filament-sidebar-editor/main/docs/images/more/scheduler-pro.jpg" alt="Scheduler Pro"></a><br><b><a href="https://scheduler-pro.hoceine.com?ref=sidebar-editor">Scheduler Pro</a></b><br>Resource timelines, Gantt with dependencies, availability-aware booking, recurring events and Kanban boards.</td>
</tr>
</table>

## Contributing

Open an issue first for larger changes. After editing `resources/css`, run `npm run build` to rebuild `dist/`, then run the tests above.

Report security issues privately as described in [SECURITY.md](SECURITY.md). Release notes are in [CHANGELOG.md](CHANGELOG.md).

## Credits

- [Hoceine El Idrissi](https://github.com/HoceineEl)
- Built on [Filament](https://filamentphp.com) and [Livewire](https://livewire.laravel.com)

## License

MIT. See [LICENSE.md](LICENSE.md).
