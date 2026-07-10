<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'traveller_id',
        'ride_type_id',
        'company_id',
        'driver_id',
        'vehicle_id',
        'flight_number',
        'scheduled_arrival_at',
        'party_size',
        'luggage_size',
        'pickup_terminal',
        'destination_name',
        'quoted_price_usd',
        'quoted_price_lbp',
        'status',
        'company_accepted_at',
        'driver_assigned_at',
        'driver_accepted_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_arrival_at' => 'datetime',
            'party_size' => 'integer',
            'quoted_price_usd' => 'decimal:2',
            'quoted_price_lbp' => 'decimal:2',
            'company_accepted_at' => 'datetime',
            'driver_assigned_at' => 'datetime',
            'driver_accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function traveller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traveller_id');
    }

    public function rideType(): BelongsTo
    {
        return $this->belongsTo(RideType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            TransportCompany::class,
            'company_id'
        );
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }
}