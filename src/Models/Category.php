<?php

namespace LaravelEnso\Categories\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LaravelEnso\DynamicMethods\Traits\Abilities;
use LaravelEnso\Helpers\Traits\AvoidsDeletionConflicts;
use LaravelEnso\Tables\Traits\TableCache;

class Category extends Model
{
    use AvoidsDeletionConflicts, Abilities, TableCache;

    protected $guarded = ['id'];

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function recursiveParent()
    {
        return $this->parent()->with('recursiveParent');
    }

    public function subcategories()
    {
        return $this->hasMany(static::class, 'parent_id')
            ->orderBy('order_index');
    }

    public function recursiveSubcategories()
    {
        return $this->subcategories()
            ->with('recursiveSubcategories');
    }

    public function scopeTopLevel(Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeHasChildren(Builder $query)
    {
        return $query->has('subcategories');
    }

    public function move(int $orderIndex, ?int $parentId)
    {
        $order = $orderIndex >= $this->order_index && $this->parent_id === $parentId
            ? 'asc'
            : 'desc';

        $this->update([
            'parent_id' => $parentId,
            'order_index' => $orderIndex,
        ]);

        self::whereParentId($parentId)
            ->orderBy('order_index')
            ->orderBy('updated_at', $order)
            ->get()
            ->each(fn ($category, $index) => $category
                ->update(['order_index' => $index + 1]));
    }

    public static function tree()
    {
        return self::topLevel()
            ->with('recursiveSubcategories')
            ->get();
    }

    public function getParentTreeAttribute()
    {
        return $this->parentTree();
    }

    public function parentTree(): Collection
    {
        $tree = new Collection();
        $category = $this;
        $category->attributes['parent'] = $category->recursiveParent;
        $tree->push($category);

        while ($category = $category->parent) {
            $tree->prepend($category);
        }

        unset($this->recursiveParent);

        return $tree;
    }

    public function currentAndBelowIds(): Collection
    {
        if (! $this->relationLoaded('recursiveSubcategories')) {
            $this->load('recursiveSubcategories');
        }

        return $this->flatten($this->recursiveSubcategories)
            ->prepend($this->id);
    }

    private function flatten(Collection $categories)
    {
        return $categories->reduce(
            fn ($flatten, $category) => $flatten->push($category->id)
                ->when($category->recursiveSubcategories->isNotEmpty(), fn ($flatten) => $flatten
                    ->concat($this->flatten($category->recursiveSubcategories))),
            new Collection()
        );
    }

    public function isParent()
    {
        return $this->subcategories()->count() > 0;
    }

    public function level()
    {
        return $this->parent_id
            ? $this->parent->level() + 1
            : 0;
    }
}
