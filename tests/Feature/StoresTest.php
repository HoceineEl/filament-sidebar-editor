<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use HoceineEl\SidebarEditor\Enums\LayoutScope;
use HoceineEl\SidebarEditor\Models\SavedLayout;
use HoceineEl\SidebarEditor\SidebarEditorPlugin;
use HoceineEl\SidebarEditor\Stores\TenantStore;
use HoceineEl\SidebarEditor\Stores\UserStore;
use HoceineEl\SidebarEditor\Tests\Fixtures\ArrayStore;

const LAYOUT = [['key' => 'sales', 'hidden' => true, 'items' => []]];

it('stores the layout on the tenant column', function (): void {
    $team = team();
    openPanel('admin', $team);
    $store = SidebarEditorPlugin::get()->getStore();

    $store->put(Filament::getCurrentPanel(), LAYOUT);

    expect($store)->toBeInstanceOf(TenantStore::class)
        ->and($team->fresh()->navigation)->toBe(['groups' => LAYOUT])
        ->and($store->get(Filament::getCurrentPanel()))->toBe(LAYOUT);

    $store->put(Filament::getCurrentPanel(), null);

    expect($team->fresh()->navigation)->toBeNull();
});

it('reads a tenant column without an array cast', function (): void {
    $team = team();
    openPanel('admin', $team);
    $team->newQuery()->whereKey($team)->update(['navigation' => json_encode(['groups' => LAYOUT])]);
    Filament::setTenant($team->fresh()->mergeCasts(['navigation' => 'string']), isQuiet: true);

    expect((new TenantStore)->get(Filament::getCurrentPanel()))->toBe(LAYOUT);
});

it('stores one layout per user', function (): void {
    $ada = openPanel('personal');
    $store = SidebarEditorPlugin::get()->getStore();
    $store->put(Filament::getCurrentPanel(), LAYOUT);

    expect($store)->toBeInstanceOf(UserStore::class)
        ->and(SidebarEditorPlugin::get()->getScope())->toBe(LayoutScope::User)
        ->and(sidebar())->not->toHaveKey('sales');

    openPanel('personal', user: user('grace@example.com'));

    expect($store->get(Filament::getCurrentPanel()))->toBeNull()
        ->and(sidebar())->toHaveKey('sales')
        ->and(SavedLayout::query()->ownedBy($ada)->forPanel('personal')->sole()->groups)->toBe(LAYOUT);
});

it('ignores the user store for guests', function (): void {
    Filament::setCurrentPanel('personal');
    $store = new UserStore;
    $store->put(Filament::getCurrentPanel(), LAYOUT);

    expect($store->get(Filament::getCurrentPanel()))->toBeNull()
        ->and(SavedLayout::query()->count())->toBe(0);
});

it('stores one layout for the whole panel', function (): void {
    openPanel('global');
    $store = SidebarEditorPlugin::get()->getStore();
    $store->put(Filament::getCurrentPanel(), LAYOUT);
    $store->put(Filament::getCurrentPanel(), [['key' => 'insights', 'hidden' => true, 'items' => []]]);

    openPanel('global', user: user('grace@example.com'));

    expect(sidebar())->toHaveKey('sales')->insights->toBe(['reports'])
        ->and(SavedLayout::query()->count())->toBe(1);

    $store->put(Filament::getCurrentPanel(), null);

    expect(SavedLayout::query()->count())->toBe(0);
});

it('uses a custom store class', function (): void {
    openPanel('global');
    $plugin = SidebarEditorPlugin::get()->store(ArrayStore::class);
    $plugin->getStore()->put(Filament::getCurrentPanel(), LAYOUT);

    expect($plugin->getScope())->toBe(LayoutScope::Custom)
        ->and(ArrayStore::$layouts['global'])->toBe(LAYOUT)
        ->and(sidebar())->not->toHaveKey('sales');
});

it('uses closures as a store', function (): void {
    openPanel('global');
    $saved = null;
    $plugin = SidebarEditorPlugin::get()->storeUsing(
        get: fn () => $saved,
        put: function (?array $groups) use (&$saved): void {
            $saved = $groups;
        },
    );

    $plugin->getStore()->put(Filament::getCurrentPanel(), LAYOUT);

    expect($saved)->toBe(LAYOUT)
        ->and(sidebar())->toHaveKey('sales');
});
