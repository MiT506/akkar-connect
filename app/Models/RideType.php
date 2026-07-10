<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RideType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label_en',
        'label_ar',
        'description_en',
        'description_ar',
        'service_mode',
        'vehicle_category',
        'seat_capacity',
        'base_price_usd',
        'price_per_km_usd',
        'default_eta_minutes',
        'is_active',
    ];

    protected $casts = [
        'seat_capacity' => 'integer',
        'base_price_usd' => 'decimal:2',
        'price_per_km_usd' => 'decimal:2',
        'default_eta_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}