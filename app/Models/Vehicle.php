<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'plate_number',
        'vehicle_type',
        'make',
        'model',
        'year',
        'seat_capacity',
        'luggage_capacity',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'seat_capacity' => 'integer',
        'luggage_capacity' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(TransportCompany::class, 'company_id');
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}