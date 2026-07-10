<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_phone',
        'contact_email',
        'status',
        'dispatch_timeout_seconds',
    ];

    protected $casts = [
        'dispatch_timeout_seconds' => 'integer',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'company_id');
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class, 'company_id');
    }
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'company_id');
    }
}