<?php

declare(strict_types=1);

namespace HoceineEl\SidebarEditor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $panel
 * @property ?string $owner_type
 * @property int|string|null $owner_id
 * @property list<array{key: string, hidden: bool, items: list<array{key: string, hidden: bool}>}> $groups
 */
class SavedLayout extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('sidebar-editor.table', 'sidebar_editor_layouts');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['groups' => 'array'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForPanel(Builder $query, string $panel): void
    {
        $query->where('panel', $panel);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOwnedBy(Builder $query, ?Model $owner): void
    {
        $owner === null
            ? $query->whereNull('owner_type')->whereNull('owner_id')
            : $query->whereMorphedTo('owner', $owner);
    }
}
