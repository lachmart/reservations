<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'start_date',
        'end_date',
        'price',
        'note',
        'is_active',
        'is_paid',
        'event_place',
        'event_type',
        'delivery',
        'delivery_zone',
        'delivery_price',
        'service',
        'source',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_paid' => 'boolean',
        'delivery' => 'boolean',
        'delivery_price' => 'decimal:2',
        'service' => 'boolean',
    ];

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }
}
