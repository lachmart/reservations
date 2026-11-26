<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A catalog section (Zvuk, Světla, …) or one of its subcategories (Mikrofony,
 * Mixpulty, …). Two levels only: a subcategory's parent is always a section.
 * A section's listing shows its own products and those of its subcategories.
 */
class Category extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if (empty($category->slug) && ! empty($category->name)) {
                $category->slug = self::uniqueSlug($category->name, $category->getKey());
            }
        });

        // Subcategories of a deleted section become sections themselves.
        static::deleting(function (self $category) {
            static::where('parent_id', $category->getKey())->update(['parent_id' => null]);
        });
    }

    public static function uniqueSlug(string $name, $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategorie';
        $slug = $base;
        $i = 2;
        while (self::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'position',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'position' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('name');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    /** The section this category belongs to (itself for a section). */
    public function root(): self
    {
        return $this->parent ?? $this;
    }

    /** "Zvuk › Mikrofony" for a subcategory, the plain name for a section. */
    public function getFullNameAttribute(): string
    {
        return $this->parent ? $this->parent->name.' › '.$this->name : $this->name;
    }

    /**
     * Ids whose products belong to this category's listing: itself and its
     * subcategories.
     *
     * @return list<int>
     */
    public function familyIds(): array
    {
        return array_values(array_unique(array_merge(
            [(int) $this->getKey()],
            static::where('parent_id', $this->getKey())->pluck('id')->map(fn ($id) => (int) $id)->all(),
        )));
    }

    /**
     * All categories in menu order, sections each followed by their
     * subcategories, as id => "Zvuk › Mikrofony" (for admin pickers).
     *
     * @return array<int, string>
     */
    public static function treeOptions(): array
    {
        $all = static::query()->ordered()->get();
        $children = $all->whereNotNull('parent_id')->groupBy('parent_id');
        $options = [];

        foreach ($all->whereNull('parent_id') as $section) {
            $options[$section->id] = $section->name;
            foreach ($children[$section->id] ?? [] as $child) {
                $options[$child->id] = $section->name.' › '.$child->name;
            }
        }
        // Subcategories whose section is gone.
        foreach ($all->whereNotNull('parent_id') as $orphan) {
            $options[$orphan->id] ??= $orphan->name;
        }

        return $options;
    }

    /**
     * Sections with their subcategories, for the menu and the catalog
     * navigation. Only categories with rentable products are included; a
     * section counts the products of its subcategories.
     *
     * @return Collection<int, self> sections, each with `children` set and an
     *                               `available_count` attribute on every node
     */
    public static function menu(): Collection
    {
        $all = static::query()
            ->withCount(['products as own_count' => fn ($q) => $q->available()])
            ->ordered()
            ->get();

        $byParent = $all->groupBy(fn (self $c) => (int) $c->parent_id);

        return $all->whereNull('parent_id')
            ->map(function (self $section) use ($byParent) {
                $children = ($byParent[$section->id] ?? new Collection)
                    ->filter(fn (self $c) => $c->own_count > 0)
                    ->each(function (self $c) use ($section) {
                        $c->available_count = $c->own_count;
                        $c->setRelation('parent', $section);
                    })
                    ->values();

                $section->setRelation('children', $children);
                $section->available_count = $section->own_count + $children->sum('own_count');

                return $section;
            })
            ->filter(fn (self $section) => $section->available_count > 0)
            ->values();
    }
}
