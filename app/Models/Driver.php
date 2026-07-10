<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = [
        'company_id',
        'vehicle_id',
        'first_name',
        'last_name',
        'phone',
        'license_number',
        'rating',
        'status',
        'current_location',
    ];

    protected $casts = [
        'rating'=>'float'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(TransportCompany::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}