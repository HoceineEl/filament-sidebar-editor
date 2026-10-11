<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Stores;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class UserStore extends TableStore
{
    protected bool $requiresOwner = true;

    protected function owner(): ?Model
    {
        $user = Filament::auth()->user();

        return $user instanceof Model ? $user : null;
    }
}
