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
