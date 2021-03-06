<?php

namespace LaravelEnso\Categories\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use LaravelEnso\Categories\Scopes\Ordered;
use LaravelEnso\DynamicMethods\Traits\Abilities;
use LaravelEnso\Helpers\Traits\AvoidsDeletionConflicts;
use LaravelEnso\Tables\Traits\TableCache;

class Category extends Model
{
    use AvoidsDeletionConflicts, Abilities, HasFactory, TableCache;

    protected $guarded = ['id'];

    public function parent(): Relation
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function recursiveParent(): Relation
    {
        return $this->parent()->with('recursiveParent');
    }

    public function subcategories(): Relation
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function recursiveSubcategories(): Relation
    {
        return $this->subcategories()
            ->with('recursiveSubcategories');
    }

    public function scopeTopLevel(Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    public function move(int $orderIndex, ?int $parentId)
    {
        $oldParentId = $this->parent_id;

        $order = $orderIndex >= $this->order_index
            && $oldParentId === $parentId
            ? 'asc'
            : 'desc';

        $this->update([
            'parent_id' => $parentId,
            'order_index' => $orderIndex,
        ]);

        self::reorder($this->parent_id, $order);

        if ($oldParentId !== $this->parent_id) {
            self::reorder($oldParentId);
        }
    }

    public static function reorder(?int $parentId, string $order = 'asc')
    {
        self::whereParentId($parentId)
            ->orderBy('updated_at', $order)
            ->get()
            ->each(fn ($group, $index) => $group
                ->update(['order_index' => $index + 1]));
    }

    public static function tree(): Collection
    {
        return self::topLevel()
            ->with('recursiveSubcategories')
            ->get();
    }

    public function parentTree(): Collection
    {
        $category = $this;

        $tree = Collection::wrap($category);

        while ($category = $category->recursiveParent) {
            $tree->prepend($category);
        }

        return $tree;
    }

    public function flattenCurrentAndBelowIds(): Collection
    {
        return $this->flattenCurrentAndBelow()
            ->pluck('id');
    }

    public function flattenCurrentAndBelow(): Collection
    {
        return $this->recursiveSubcategories
            ->map(fn ($cat) => $cat->flattenCurrentAndBelow())
            ->flatten()
            ->prepend($this);
    }

    public function isParent(): bool
    {
        return $this->subcategories()->exists();
    }

    public function level(): int
    {
        return $this->parent_id
            ? $this->parent->level() + 1
            : 0;
    }

    public function depth(): int
    {
        return $this->recursiveSubcategories
                ->map(fn ($category) => $category->depth() + 1)
                ->max() ?? 0;
    }

    protected static function booted()
    {
        static::addGlobalScope(new Ordered());
    }
}
