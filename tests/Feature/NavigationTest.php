<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Navigation\NavigationManager;
use HoceineEl\SidebarEditor\Models\SavedLayout;
use HoceineEl\SidebarEditor\Navigation\LayoutNavigationManager;
use HoceineEl\SidebarEditor\Pages\SidebarEditor;
use HoceineEl\SidebarEditor\Tests\Fixtures\CustomNavigationManager;

const HIDE_COUPONS_AND_INSIGHTS = [
    ['key' => 'insights', 'hidden' => true, 'items' => []],
    ['key' => 'sales', 'hidden' => false, 'items' => [['key' => 'coupons', 'hidden' => true]]],
];

it('wraps the bound navigation manager', function (): void {
    openPanel('other');

    expect(app(NavigationManager::class))->toBeInstanceOf(LayoutNavigationManager::class);
});

it('reads the tenant column in its existing format', function (): void {
    openPanel('admin', team('acme', ['navigation' => ['groups' => HIDE_COUPONS_AND_INSIGHTS]]));

    expect(sidebar())
        ->not->toHaveKey('insights')
        ->sales->toBe(['orders']);
});

it('keeps each tenant\'s sidebar to itself', function (): void {
    $acme = team('acme', ['navigation' => ['groups' => HIDE_COUPONS_AND_INSIGHTS]]);
    $globex = team('globex');
    $user = openPanel('admin', $acme);
    $globex->users()->attach($user);

    expect(sidebar())->not->toHaveKey('insights');

    Filament::setTenant($globex, isQuiet: true);

    expect(sidebar())->toHaveKey('insights')->sales->toBe(['orders', 'coupons']);
});

it('leaves panels without the plugin untouched', function (): void {
    SavedLayout::create(['panel' => 'other', 'groups' => HIDE_COUPONS_AND_INSIGHTS]);
    openPanel('other');

    expect(sidebar())
        ->toHaveKey('insights')
        ->sales->toBe(['orders', 'coupons']);
});

it('only registers the editor page on panels with the plugin', function (): void {
    expect(Filament::getPanel('admin')->getPages())->toContain(SidebarEditor::class)
        ->and(Filament::getPanel('other')->getPages())->not->toContain(SidebarEditor::class);
});

it('picks up pages added after the layout was saved', function (): void {
    openPanel('admin', team('acme', ['navigation' => ['groups' => [
        ['key' => 'sales', 'hidden' => false, 'items' => [['key' => 'coupons', 'hidden' => false]]],
    ]]]));

    expect(sidebar())
        ->sales->toBe(['coupons', 'orders'])
        ->inventory->toBe(['products', 'categories']);
});

it('still applies when another package rebinds the navigation manager', function (): void {
    app()->scoped(NavigationManager::class, fn (): NavigationManager => new CustomNavigationManager);
    openPanel('admin', team('acme', ['navigation' => ['groups' => [['key' => 'extra', 'hidden' => false, 'items' => [['key' => 'help', 'hidden' => true]]], ...HIDE_COUPONS_AND_INSIGHTS]]]));

    expect(app(NavigationManager::class))->toBeInstanceOf(LayoutNavigationManager::class)
        ->and(sidebar())->not->toHaveKey('extra')->not->toHaveKey('insights');
});
