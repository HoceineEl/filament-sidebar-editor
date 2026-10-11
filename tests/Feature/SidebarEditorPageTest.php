<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use HoceineEl\SidebarEditor\Models\SavedLayout;
use HoceineEl\SidebarEditor\Pages\SidebarEditor;
use HoceineEl\SidebarEditor\Tests\Fixtures\AdminPanelProvider;

use function Pest\Livewire\livewire;

it('renders the editor with counts and the preview', function (): void {
    openPanel('admin', team());

    livewire(SidebarEditor::class)
        ->assertOk()
        ->assertSee('Sidebar')
        ->assertSee('Top of the sidebar')
        ->assertSee('1 of 1 shown')
        ->assertSee('Always shown')
        ->assertSee('Preview')
        ->assertDontSee('You have unsaved changes.');
});

it('lists the live navigation, child pages included', function (): void {
    openPanel('admin', team());

    expect(livewire(SidebarEditor::class)->get('sidebar'))->toBe([
        ['key' => 'main', 'hidden' => false, 'items' => [['key' => 'dashboard', 'hidden' => false]]],
        ['key' => 'settings', 'hidden' => false, 'items' => [['key' => 'sidebar', 'hidden' => false]]],
        ['key' => 'sales', 'hidden' => false, 'items' => [['key' => 'orders', 'hidden' => false], ['key' => 'coupons', 'hidden' => false]]],
        ['key' => 'inventory', 'hidden' => false, 'items' => [['key' => 'products', 'hidden' => false], ['key' => 'categories', 'hidden' => false]]],
        ['key' => 'insights', 'hidden' => false, 'items' => [['key' => 'reports', 'hidden' => false]]],
    ]);
});

it('hides a page, a child page and a group once saved', function (): void {
    $team = team();
    openPanel('admin', $team);

    livewire(SidebarEditor::class)
        ->call('toggleItem', 'sales', 'coupons')
        ->call('toggleItem', 'inventory', 'categories')
        ->call('toggleGroup', 'insights')
        ->assertSee('You have unsaved changes.')
        ->assertActionEnabled('save')
        ->callAction('save')
        ->assertNotified('Sidebar saved')
        ->assertRedirect(SidebarEditor::getUrl());

    expect(sidebar())
        ->not->toHaveKey('insights')
        ->sales->toBe(['orders'])
        ->inventory->toBe(['products'])
        ->and($team->fresh()->navigation['groups'])->toHaveCount(5);
});

it('reorders groups and pages', function (): void {
    openPanel('admin', team());

    livewire(SidebarEditor::class)
        ->call('reorderGroups', ['insights', 'sales'])
        ->call('reorderItems', 'sales', ['coupons', 'orders'])
        ->callAction('save');

    expect(array_keys(sidebar()))->toBe(['main', 'insights', 'sales', 'settings', 'inventory'])
        ->and(sidebar()['sales'])->toBe(['coupons', 'orders']);
});

it('keeps the fixed main group first when groups are reordered', function (): void {
    openPanel('admin', team());

    $component = livewire(SidebarEditor::class)->call('reorderGroups', ['sales', 'insights']);

    expect(array_column($component->get('sidebar'), 'key'))->toBe(['main', 'sales', 'insights', 'settings', 'inventory'])
        ->and(livewire(SidebarEditor::class)->call('toggleGroup', 'main')->instance()->isDirty())->toBeFalse();
});

it('never hides the editor page or its group', function (): void {
    openPanel('admin', team());

    livewire(SidebarEditor::class)
        ->call('toggleItem', 'settings', 'sidebar')
        ->call('toggleGroup', 'settings')
        ->assertActionDisabled('save')
        ->assertSet('sidebar.1.hidden', false);
});

it('shows everything and discards changes', function (): void {
    openPanel('admin', team('acme', ['navigation' => ['groups' => [['key' => 'sales', 'hidden' => true, 'items' => [['key' => 'orders', 'hidden' => true]]]]]]));

    livewire(SidebarEditor::class)
        ->call('showEverything')
        ->assertSet('sidebar.1.hidden', false)
        ->assertSet('sidebar.1.items.0.hidden', false)
        ->assertActionVisible('discard')
        ->callAction('discard')
        ->assertSet('sidebar.1.hidden', true)
        ->assertActionHidden('discard');
});

it('restores the default sidebar', function (): void {
    $team = team('acme', ['navigation' => ['groups' => [['key' => 'sales', 'hidden' => true, 'items' => []]]]]);
    openPanel('admin', $team);

    livewire(SidebarEditor::class)
        ->callAction('reset')
        ->assertNotified('Default sidebar restored');

    expect($team->fresh()->navigation)->toBeNull()
        ->and(sidebar())->toHaveKey('sales');
});

it('saves to the open tenant only', function (): void {
    $acme = team('acme');
    $globex = team('globex');
    $user = openPanel('admin', $acme);
    $globex->users()->attach($user);

    livewire(SidebarEditor::class)->call('toggleGroup', 'sales')->callAction('save');

    expect($acme->fresh()->navigation)->not->toBeNull()
        ->and($globex->fresh()->navigation)->toBeNull();

    Filament::setTenant($globex, isQuiet: true);

    expect(livewire(SidebarEditor::class)->get('sidebar.2'))->toMatchArray(['key' => 'sales', 'hidden' => false]);
});

it('saves per user on a user-scoped panel', function (): void {
    $ada = openPanel('personal');

    livewire(SidebarEditor::class)->call('toggleGroup', 'sales')->callAction('save');

    expect(SavedLayout::query()->ownedBy($ada)->forPanel('personal')->exists())->toBeTrue();
});

it('uses the configured slug and url', function (): void {
    $team = team();
    openPanel('admin', $team);

    expect(SidebarEditor::getUrl())->toEndWith('/admin/acme/sidebar');

    Filament::setCurrentPanel('personal');
    Filament::setTenant(null, isQuiet: true);

    expect(SidebarEditor::getUrl())->toEndWith('/personal/my-menu');
});

it('opens for authorised users', function (): void {
    $team = team();
    openPanel('admin', $team);

    $this->get('/admin/acme/sidebar')->assertOk()->assertSee('Choose which pages appear in the sidebar');
});

it('is closed when the authorize closure refuses', function (): void {
    AdminPanelProvider::$allowed = false;
    $team = team();
    openPanel('admin', $team);

    expect(SidebarEditor::canAccess())->toBeFalse()
        ->and(sidebar()['settings'] ?? [])->not->toContain('sidebar');

    $this->get('/admin/acme/sidebar')->assertForbidden();
});

it('locks the group that holds a pinned page', function (): void {
    openPanel('admin', team());

    livewire(SidebarEditor::class)
        ->assertSeeHtml('aria-labelledby="sbe-group-settings"')
        ->assertDontSee('$isFixed');
});
