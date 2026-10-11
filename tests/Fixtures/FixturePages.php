<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Tests\Fixtures;

use Filament\Pages\Dashboard;
use HoceineEl\SidebarEditor\Tests\Fixtures\Pages\Categories;
use HoceineEl\SidebarEditor\Tests\Fixtures\Pages\Coupons;
use HoceineEl\SidebarEditor\Tests\Fixtures\Pages\Orders;
use HoceineEl\SidebarEditor\Tests\Fixtures\Pages\Products;
use HoceineEl\SidebarEditor\Tests\Fixtures\Pages\Reports;

final class FixturePages
{
    /**
     * @return list<class-string>
     */
    public static function all(): array
    {
        return [Dashboard::class, Orders::class, Coupons::class, Products::class, Categories::class, Reports::class];
    }
}
