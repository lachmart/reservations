<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * Auto-generate a unique slug from the name when one isn't set (kept stable
     * afterwards so shared URLs don't break).
     */
    protected static function booted(): void
    {
        static::saving(function (self $product) {
            if (empty($product->slug) && ! empty($product->name)) {
                $product->slug = self::uniqueSlug($product->name, $product->getKey());
            }
        });

        // Old URLs keep working: a renamed product's old slug points to the new
        // URL, a deleted product's slug to its category (301, see routes/web.php).
        static::updated(function (self $product) {
            $old = $product->getOriginal('slug');
            if ($product->wasChanged('slug') && filled($old)) {
                ProductRedirect::remember($old, '/products/'.$product->slug);
            }
            // If this slug was once redirected elsewhere, the live product wins.
            ProductRedirect::where('old_slug', $product->slug)->delete();
        });

        static::created(function (self $product) {
            ProductRedirect::where('old_slug', $product->slug)->delete();
        });

        static::deleted(function (self $product) {
            if (filled($product->slug)) {
                ProductRedirect::remember($product->slug, $product->categoryUrl());
            }
        });
    }

    /** Listing page of the product's category, or the whole catalog. */
    public function categoryUrl(): string
    {
        $slug = $this->category()->value('slug');

        return $slug ? '/products?category='.$slug : '/products';
    }

    public static function uniqueSlug(string $name, $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produkt';
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
     * Scope: products listed under a category, i.e. in it or in one of its
     * subcategories.
     */
    public function scopeInCategory(\Illuminate\Database\Eloquent\Builder $query, Category $category): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('category_id', $category->familyIds());
    }

    /**
     * Scope: only products with positive price and stock.
     */
    public function scopeAvailable(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('price_per_day', '>', 0)->where('stock', '>', 0);
    }

    public function bundleItems(): BelongsToMany
    {
        // Products that compose this product as a bundle (child products with quantities)
        return $this->belongsToMany(Product::class, 'bundle_items', 'bundle_product_id', 'product_id')
            ->withPivot('amount');
    }

    /**
     * Sets (bundles) that contain this product.
     */
    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'bundle_items', 'product_id', 'bundle_product_id')
            ->withPivot('amount');
    }

    /**
     * Whether this product is a bundle (has bundle item relations).
     */
    public function isBundle(): bool
    {
        // Use exists() for efficiency (avoids COUNT(*))
        return $this->bundleItems()->exists();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'category_id',
        'price_per_day',
        'stock',
    ];

    /**
     * `status` is an unused leftover from the original scaffold, but the live
     * products table has it NOT NULL without a default, so new rows get ''.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => '',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
        'price_per_day' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function ports(): BelongsToMany
    {
        return $this->belongsToMany(Port::class)->withPivot('amount');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Sum of reserved amount overlapping the given date range [start, end].
     */
    public function reservedAmountBetween(string $start, string $end): int
    {
        return (int) $this->reservationItems()
            ->whereHas('reservation', function ($q) use ($start, $end) {
                $q->where('start_date', '<', $end)
                  ->where('end_date', '>', $start);
            })
            ->sum('amount');
    }

    /**
     * Calculate available stock for the given date range.
     */
    public function availableForRange(string $start, string $end): int
    {
        $stock = (int) ($this->stock ?? 0);
        $reserved = $this->reservedAmountBetween($start, $end);
        $available = $stock - $reserved;
        return $available > 0 ? $available : 0;
    }
    /**
     * URL of the product's main image, with fallback across collections.
     * Photos are stored in the "avatar" collection (Filament + MCP), with an
     * optional "product-images" gallery. Returns null when there is no image.
     */
    public function mainImageUrl(): ?string
    {
        foreach (['avatar', 'product-images', 'default'] as $collection) {
            $url = $this->getFirstMediaUrl($collection);
            if (! empty($url)) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Image to show for the product: its own photo, or for a set without one, the
     * photo of its main component (largest share of the set's value, e.g. the
     * speakers rather than a stand).
     */
    public function displayImageUrl(): ?string
    {
        if ($url = $this->mainImageUrl()) {
            return $url;
        }

        $byWeight = $this->bundleItems->sortByDesc(
            fn (Product $child) => (float) $child->price_per_day * max(1, (int) $child->pivot->amount)
        );

        foreach ($byWeight as $child) {
            if ($url = $child->mainImageUrl()) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Which icon to show when the product has no photo yet.
     * Matched on the product name first, then falls back to the category.
     */
    public function placeholderKind(): string
    {
        $section = $this->category?->root()->slug;

        // Stage gear (stands, truss) — brand names like "Elation" would otherwise match lights.
        if ($section === 'stage') {
            return 'stand';
        }

        // Sets ("… + mikrofony" in the name would otherwise read as a mic).
        if ($section === 'sety') {
            return 'package';
        }

        // A subcategory that says it all (names like "sE8 Stereo (pár)" would
        // otherwise read as a PAR light).
        if ($this->category?->slug === 'mikrofony') {
            return 'mic';
        }

        $name = Str::slug((string) $this->name, ' ');

        $rules = [
            'package' => ['balicek'],
            'stagebox' => ['stagebox', 'stage box'],
            'reel' => ['buben', 'bubnu'],
            'power' => ['rozvadec', 'pdb', 'amprack', 'amp rack', 'powerbox', 'h07rn', 'sez 32a', 'sez 16a'],
            'cable' => ['kabel', 'xlr', 'speakon', 'cat 5', 'cat5', 'tf34', 'neutrik', 'klotz'],
            'mic' => ['mikrofon', 'shure', 'sennheiser', 'audix', 'akg', 'dji', 'rode'],
            'stand' => ['stojan', 'konigmeyer', 'konig', 'adam hall s', 'gravity'],
            'haze' => ['haze', 'hazer', 'mlh'],
            'light' => ['svetlo', 'elation', 'chauvet', 'cameo', 'fractal', 'varytec', 'spot', 'wash', 'par '],
        ];

        foreach ($rules as $kind => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($name.' ', $needle)) {
                    return $kind;
                }
            }
        }

        return match ($section) {
            'zvuk' => 'speaker',
            'svetla' => 'light',
            'stage' => 'stand',
            'distro' => 'power',
            'pocitace' => 'laptop',
            default => 'generic',
        };
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->keepOriginalImageFormat()
            ->fit(Fit::Contain, 50, 50)
            ->nonQueued();
        $this->addMediaConversion('thumb')
            ->keepOriginalImageFormat()
            ->width(100)  // Resize to 100px width
            ->height(100) // Resize to 100px height
            // Optional: sharpen the image for clarity
            ->sharpen(10)
            ->nonQueued();
    }
}
