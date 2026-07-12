<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\RideType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'traveller_name' => ['required', 'string', 'max:255'],
            'traveller_email' => ['required', 'email', 'max:255'],
            'traveller_phone' => ['nullable', 'string', 'max:30'],

            'ride_type_id' => [
                'required',
                'integer',
                Rule::exists('ride_types', 'id')->where('is_active', true),
            ],

            'flight_number' => ['required', 'string', 'max:30'],
            'scheduled_arrival_at' => ['required', 'date'],

            'party_size' => ['required', 'integer', 'min:1', 'max:20'],
            'luggage_size' => ['required', 'in:SMALL,MEDIUM,LARGE'],

            'destination_name' => ['required', 'string', 'max:255'],
            'destination_lat' => ['required', 'numeric', 'between:-90,90'],
            'destination_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $booking = DB::transaction(function () use ($validated): Booking {
            $traveller = User::query()->firstOrCreate(
                ['email' => $validated['traveller_email']],
                [
                    'name' => $validated['traveller_name'],
                    'phone' => $validated['traveller_phone'] ?? null,
                    'password' => bcrypt(str()->random(32)),
                    'role' => 'TRAVELLER',
                    'preferred_language' => 'en',
                    'status' => 'ACTIVE',
                ]
            );

            $rideType = RideType::query()->findOrFail($validated['ride_type_id']);

            $booking = Booking::query()->create([
                'traveller_id' => $traveller->id,
                'ride_type_id' => $rideType->id,
                'flight_number' => strtoupper($validated['flight_number']),
                'scheduled_arrival_at' => $validated['scheduled_arrival_at'],
                'party_size' => $validated['party_size'],
                'luggage_size' => $validated['luggage_size'],
                'pickup_terminal' => 'Main Terminal',
                'destination_name' => $validated['destination_name'],
                'quoted_price_usd' => $rideType->base_price_usd,
                'quoted_price_lbp' => null,
                'status' => 'PENDING',
            ]);

            DB::statement(
                'UPDATE bookings
                 SET destination_location = ST_SetSRID(ST_MakePoint(?, ?), 4326)
                 WHERE id = ?',
                [
                    $validated['destination_lng'],
                    $validated['destination_lat'],
                    $booking->id,
                ]
            );

            BookingStatusHistory::query()->create([
                'booking_id' => $booking->id,
                'changed_by_user_id' => $traveller->id,
                'old_status' => null,
                'new_status' => 'PENDING',
                'notes' => 'Booking created by traveller.',
                'metadata' => [
                    'source' => 'traveller_app',
                ],
            ]);

            return $booking->fresh([
                'traveller',
                'rideType',
                'statusHistories',
            ]);
        });

        return response()->json([
            'message' => 'Booking created successfully.',
            'data' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'flight_number' => $booking->flight_number,
                'scheduled_arrival_at' => $booking->scheduled_arrival_at,
                'party_size' => $booking->party_size,
                'luggage_size' => $booking->luggage_size,
                'destination_name' => $booking->destination_name,
                'price_usd' => $booking->quoted_price_usd,
                'ride_type' => [
                    'id' => $booking->rideType->id,
                    'code' => $booking->rideType->code,
                    'label_en' => $booking->rideType->label_en,
                    'label_ar' => $booking->rideType->label_ar,
                ],
                'traveller' => [
                    'id' => $booking->traveller->id,
                    'name' => $booking->traveller->name,
                    'email' => $booking->traveller->email,
                    'phone' => $booking->traveller->phone,
                ],
            ],
        ], 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load([
            'traveller',
            'rideType',
            'company',
            'driver',
            'vehicle',
            'statusHistories',
        ]);

        return response()->json([
            'data' => $booking,
        ]);
    }
}