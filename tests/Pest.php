<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use HoceineEl\SidebarEditor\Support\SidebarLayout;
use HoceineEl\SidebarEditor\Tests\Fixtures\AdminPanelProvider;
use HoceineEl\SidebarEditor\Tests\Fixtures\ArrayStore;
use HoceineEl\SidebarEditor\Tests\Fixtures\Team;
use HoceineEl\SidebarEditor\Tests\Fixtures\User;
use HoceineEl\SidebarEditor\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

uses()->afterEach(function (): void {
    AdminPanelProvider::$allowed = true;
    ArrayStore::$layouts = [];
})->in(__DIR__);

function team(string $slug = 'acme', array $attributes = []): Team
{
    return Team::create(['name' => ucfirst($slug), 'slug' => $slug, ...$attributes]);
}

function user(string $email = 'ada@example.com'): User
{
    return User::create(['name' => 'Ada', 'email' => $email, 'password' => 'secret']);
}

/**
 * Signs in and opens the panel, inside a team when one is given.
 */
function openPanel(string $panel, ?Team $team = null, ?User $user = null): User
{
    $user ??= user();
    $team?->users()->attach($user);

    test()->actingAs($user);
    Filament::setCurrentPanel($panel);
    Filament::setTenant($team, isQuiet: true);

    return $user;
}

/**
 * @return array<string, list<string>>
 */
function sidebar(): array
{
    app()->forgetInstance(NavigationManager::class);
    $panel = Filament::getCurrentPanel();

    return collect($panel->getNavigation())
        ->mapWithKeys(fn (NavigationGroup $group): array => [
            SidebarLayout::groupKey($group, $panel->getNavigationGroups()) => collect($group->getItems())
                ->flatMap(fn (NavigationItem $item): array => [SidebarLayout::itemKey($item), ...collect($item->getChildItems())->map(fn (NavigationItem $child): string => SidebarLayout::itemKey($child))->all()])
                ->all(),
        ])
        ->all();
}
