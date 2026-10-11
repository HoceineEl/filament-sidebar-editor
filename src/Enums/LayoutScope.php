<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Enums;

enum LayoutScope: string
{
    case Tenant = 'tenant';
    case User = 'user';
    case Global = 'global';
    case Custom = 'custom';
}
