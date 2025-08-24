<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
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

    public function reservedAmountBetween(string $start, string $end): int
    {
        return (int) $this->reservationItems()
            ->whereHas('reservation', function ($q) use ($start, $end) {
                $q->where('start_date', '<', $end)
                  ->where('end_date', '>', $start);
            })
            ->sum('amount');
    }

    public function availableForRange(string $start, string $end): int
    {
        $stock = (int) ($this->stock ?? 0);
        $reserved = $this->reservedAmountBetween($start, $end);
        $available = $stock - $reserved;
        return $available > 0 ? $available : 0;
    }
//    public function registerMediaConversions(?Media $media = null): void
//    {
//        $this
//            ->addMediaConversion('preview')
//            ->fit(Fit::Contain, 50, 50)
//            ->nonQueued();
//        $this->addMediaConversion('thumb')
//            ->width(100)  // Resize to 100px width
//            ->height(100) // Resize to 100px height
//            ->sharpen(10); // Optional: sharpen the image for clarity
//    }
}
