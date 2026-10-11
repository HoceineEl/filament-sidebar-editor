<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Stores;

use Filament\Facades\Filament;
use Filament\Panel;
use HoceineEl\SidebarEditor\Contracts\LayoutStore;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Keeps the layout on a column of the current tenant as `{"groups": [...]}`.
 */
class TenantStore implements LayoutStore
{
    public function __construct(protected string $column = 'navigation') {}

    public function get(Panel $panel): ?array
    {
        $value = $this->tenant()?->getAttribute($this->column);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) && is_array($value['groups'] ?? null) ? $value['groups'] : null;
    }

    public function put(Panel $panel, ?array $groups): void
    {
        $tenant = $this->tenant() ?? throw new LogicException('The tenant layout store needs a current tenant.');
        $value = match (true) {
            $groups === null => null,
            $tenant->hasCast($this->column) => ['groups' => $groups],
            default => json_encode(['groups' => $groups]),
        };

        $tenant->forceFill([$this->column => $value])->save();
    }

    protected function tenant(): ?Model
    {
        return Filament::getTenant();
    }
}
