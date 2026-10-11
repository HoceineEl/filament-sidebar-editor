<?php

declare(strict_types=1);

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use HoceineEl\SidebarEditor\Support\SidebarLayout;

beforeEach(fn () => openPanel('admin', team()));

/**
 * @return array<NavigationGroup>
 */
function groups(): array
{
    return [
        NavigationGroup::make()->items([NavigationItem::make('Dashboard')->url('/admin/acme')]),
        NavigationGroup::make('Sales')->items([
            NavigationItem::make('Orders')->url('/admin/acme/orders'),
            NavigationItem::make('Coupons')->url('/admin/acme/coupons?tab=active'),
        ]),
        NavigationGroup::make('Inventory')->items([
            NavigationItem::make('Products')->url('/admin/acme/products')->childItems([NavigationItem::make('Categories')->url('/admin/acme/categories')]),
        ]),
        NavigationGroup::make('Settings')->items([NavigationItem::make('Sidebar')->url('/admin/acme/sidebar')]),
    ];
}

/**
 * @param  array<NavigationGroup>  $groups
 * @return array<string, list<string>>
 */
function keys(array $groups): array
{
    return collect($groups)->mapWithKeys(fn (NavigationGroup $group): array => [
        SidebarLayout::groupKey($group, REGISTERED) => collect($group->getItems())
            ->flatMap(fn (NavigationItem $item): array => [SidebarLayout::itemKey($item), ...array_map(SidebarLayout::itemKey(...), $item->getChildItems())])
            ->all(),
    ])->all();
}

const REGISTERED = ['sales' => 'Sales', 'inventory' => 'Inventory', 'settings' => 'Settings'];

it('keys items by their path inside the tenant', function (): void {
    expect(keys(groups()))->toBe([
        'main' => ['dashboard'],
        'sales' => ['orders', 'coupons'],
        'inventory' => ['products', 'categories'],
        'settings' => ['sidebar'],
    ]);
});

it('leaves the navigation alone without a saved layout', function (): void {
    $groups = groups();

    expect((new SidebarLayout(null))->apply($groups, REGISTERED))->toBe($groups);
});

it('hides pages, child pages and whole groups', function (): void {
    $layout = new SidebarLayout([
        ['key' => 'sales', 'hidden' => false, 'items' => [['key' => 'coupons', 'hidden' => true]]],
        ['key' => 'inventory', 'hidden' => false, 'items' => [['key' => 'categories', 'hidden' => true]]],
        ['key' => 'settings', 'hidden' => true, 'items' => []],
    ]);

    expect(keys($layout->apply(groups(), REGISTERED)))->toBe([
        'main' => ['dashboard'],
        'sales' => ['orders'],
        'inventory' => ['products'],
    ]);
});

it('reorders groups and pages and keeps the main group on top', function (): void {
    $layout = new SidebarLayout([
        ['key' => 'inventory', 'hidden' => false, 'items' => []],
        ['key' => 'sales', 'hidden' => false, 'items' => [['key' => 'coupons', 'hidden' => false], ['key' => 'orders', 'hidden' => false]]],
        ['key' => 'main', 'hidden' => true, 'items' => []],
    ]);

    expect(keys($layout->apply(groups(), REGISTERED)))->toBe([
        'main' => ['dashboard'],
        'inventory' => ['products', 'categories'],
        'sales' => ['coupons', 'orders'],
        'settings' => ['sidebar'],
    ]);
});

it('lets the main group move and hide when it is not fixed', function (): void {
    $layout = new SidebarLayout([
        ['key' => 'sales', 'hidden' => false, 'items' => []],
        ['key' => 'main', 'hidden' => true, 'items' => []],
    ], fixedMainGroup: false);

    expect(array_keys(keys($layout->apply(groups(), REGISTERED))))->toBe(['sales', 'inventory', 'settings']);
});

it('never hides pinned pages, even inside a hidden group', function (): void {
    $layout = new SidebarLayout([
        ['key' => 'settings', 'hidden' => true, 'items' => [['key' => 'sidebar', 'hidden' => true]]],
        ['key' => 'sales', 'hidden' => false, 'items' => [['key' => 'orders', 'hidden' => true]]],
    ], pinned: ['sidebar', 'orders']);

    expect(keys($layout->apply(groups(), REGISTERED)))
        ->settings->toBe(['sidebar'])
        ->sales->toBe(['orders', 'coupons']);
});

it('merges the saved layout with new and removed pages', function (): void {
    $catalog = [
        'main' => ['label' => 'Top', 'icon' => null, 'items' => ['dashboard' => ['label' => 'Dashboard', 'icon' => null, 'children' => []]]],
        'sales' => ['label' => 'Sales', 'icon' => null, 'items' => [
            'orders' => ['label' => 'Orders', 'icon' => null, 'children' => []],
            'invoices' => ['label' => 'Invoices', 'icon' => null, 'children' => []],
        ]],
        'settings' => ['label' => 'Settings', 'icon' => null, 'items' => ['sidebar' => ['label' => 'Sidebar', 'icon' => null, 'children' => []]]],
    ];

    $layout = new SidebarLayout([
        ['key' => 'settings', 'hidden' => true, 'items' => [['key' => 'sidebar', 'hidden' => true]]],
        ['key' => 'sales', 'hidden' => false, 'items' => [['key' => 'removed', 'hidden' => true], ['key' => 'orders', 'hidden' => true]]],
        ['key' => 'gone', 'hidden' => true, 'items' => []],
    ], pinned: ['sidebar']);

    expect($layout->merge($catalog))->toBe([
        ['key' => 'main', 'hidden' => false, 'items' => [['key' => 'dashboard', 'hidden' => false]]],
        ['key' => 'settings', 'hidden' => false, 'items' => [['key' => 'sidebar', 'hidden' => false]]],
        ['key' => 'sales', 'hidden' => false, 'items' => [['key' => 'orders', 'hidden' => true], ['key' => 'invoices', 'hidden' => false]]],
    ]);
});
