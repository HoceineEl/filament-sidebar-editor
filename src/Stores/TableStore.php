<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Stores;

use Filament\Panel;
use HoceineEl\SidebarEditor\Contracts\LayoutStore;
use HoceineEl\SidebarEditor\Models\SavedLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps one layout per panel and owner in the package's own table.
 */
abstract class TableStore implements LayoutStore
{
    protected bool $requiresOwner = false;

    protected function owner(): ?Model
    {
        return null;
    }

    public function get(Panel $panel): ?array
    {
        $owner = $this->owner();

        if ($this->isMissing($owner)) {
            return null;
        }

        return $this->query($panel, $owner)->first()?->groups;
    }

    public function put(Panel $panel, ?array $groups): void
    {
        $owner = $this->owner();

        if ($this->isMissing($owner)) {
            return;
        }

        if ($groups === null) {
            $this->query($panel, $owner)->delete();

            return;
        }

        $layout = $this->query($panel, $owner)->first() ?? new SavedLayout([
            'panel' => $panel->getId(),
            'owner_type' => $owner?->getMorphClass(),
            'owner_id' => $owner?->getKey(),
        ]);

        $layout->fill(['groups' => $groups])->save();
    }

    private function isMissing(?Model $owner): bool
    {
        return $this->requiresOwner && $owner === null;
    }

    /**
     * @return Builder<SavedLayout>
     */
    private function query(Panel $panel, ?Model $owner): Builder
    {
        return SavedLayout::query()->forPanel($panel->getId())->ownedBy($owner);
    }
}
