<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
    ];

    public function ports(): BelongsToMany
    {
        return $this->belongsToMany(Port::class)->withPivot('amount');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function reservationItems(): BelongsToMany
    {
        return $this->belongsToMany(ReservationItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
