<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use HoceineEl\SidebarEditor\SidebarEditorPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Workbench\App\Filament\Pages;
use Workbench\App\Http\Middleware\SetLocale;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->brandName('Acme Store')
            ->colors(['primary' => Color::Amber])
            ->navigationGroups([
                'sales' => NavigationGroup::make()->label(fn (): string => __('Sales')),
                'catalog' => NavigationGroup::make()->label(fn (): string => __('Catalog')),
                'marketing' => NavigationGroup::make()->label(fn (): string => __('Marketing')),
                'settings' => NavigationGroup::make()->label(fn (): string => __('Settings')),
            ])
            ->pages([
                Dashboard::class,
                Pages\Orders::class,
                Pages\Customers::class,
                Pages\Invoices::class,
                Pages\Coupons::class,
                Pages\Products::class,
                Pages\Categories::class,
                Pages\Brands::class,
                Pages\Inventory::class,
                Pages\Campaigns::class,
                Pages\Newsletters::class,
                Pages\Team::class,
                Pages\Billing::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->middleware([SetLocale::class], isPersistent: true)
            ->plugin(SidebarEditorPlugin::make()->navigationGroup('settings')->navigationSort(3));
    }
}
