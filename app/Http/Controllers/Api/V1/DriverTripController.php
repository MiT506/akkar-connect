<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DriverTripController extends Controller
{
    public function currentJob(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('drivers', 'id'),
            ],
        ]);

        $booking = Booking::query()
            ->with([
                'traveller:id,name,email,phone',
                'rideType:id,code,label_en,label_ar,service_mode,vehicle_category',
                'company:id,name,contact_phone',
                'driver',
                'vehicle',
                'statusHistories',
            ])
            ->where('driver_id', $validated['driver_id'])
            ->whereIn('status', [
                'DRIVER_ASSIGNED',
                'DRIVER_ACCEPTED',
                'DRIVER_ARRIVING',
                'DRIVER_ARRIVED',
                'IN_PROGRESS',
            ])
            ->latest('driver_assigned_at')
            ->first();

        return response()->json([
            'data' => $booking,
        ]);
    }

    public function accept(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $validated = $request->validate([
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('drivers', 'id'),
            ],
        ]);

        if ((int) $booking->driver_id !== (int) $validated['driver_id']) {
            return response()->json([
                'message' => 'This booking is not assigned to this driver.',
            ], 403);
        }

        if ($booking->status !== 'DRIVER_ASSIGNED') {
            return response()->json([
                'message' => 'This job can no longer be accepted.',
                'current_status' => $booking->status,
            ], 409);
        }

        DB::transaction(function () use ($booking, $validated): void {
            $lockedBooking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $driver = Driver::query()
                ->lockForUpdate()
                ->findOrFail($validated['driver_id']);

            $oldStatus = $lockedBooking->status;

            $lockedBooking->update([
                'status' => 'DRIVER_ACCEPTED',
                'driver_accepted_at' => now(),
            ]);

            $driver->update([
                'status' => 'ON_JOB',
            ]);

            if ($lockedBooking->vehicle) {
                $lockedBooking->vehicle->update([
                    'status' => 'ON_TRIP',
                ]);
            }

            BookingStatusHistory::query()->create([
                'booking_id' => $lockedBooking->id,
                'changed_by_user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'DRIVER_ACCEPTED',
                'notes' => 'Driver accepted the assigned job.',
                'metadata' => [
                    'driver_id' => $driver->id,
                    'source' => 'driver_app',
                ],
            ]);
        });

        return response()->json([
            'message' => 'Job accepted successfully.',
            'data' => $booking->fresh([
                'traveller',
                'rideType',
                'company',
                'driver',
                'vehicle',
                'statusHistories',
            ]),
        ]);
    }

    public function decline(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $validated = $request->validate([
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('drivers', 'id'),
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        if ((int) $booking->driver_id !== (int) $validated['driver_id']) {
            return response()->json([
                'message' => 'This booking is not assigned to this driver.',
            ], 403);
        }

        if ($booking->status !== 'DRIVER_ASSIGNED') {
            return response()->json([
                'message' => 'This job can no longer be declined.',
                'current_status' => $booking->status,
            ], 409);
        }

        DB::transaction(function () use ($booking, $validated): void {
            $lockedBooking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $driver = Driver::query()
                ->lockForUpdate()
                ->findOrFail($validated['driver_id']);

            $vehicle = $lockedBooking->vehicle;
            $oldStatus = $lockedBooking->status;

            $lockedBooking->update([
                'driver_id' => null,
                'vehicle_id' => null,
                'status' => 'COMPANY_ACCEPTED',
                'driver_assigned_at' => null,
            ]);

            $driver->update([
                'status' => 'AVAILABLE',
            ]);

            if ($vehicle) {
                $vehicle->update([
                    'status' => 'AVAILABLE',
                ]);
            }

            BookingStatusHistory::query()->create([
                'booking_id' => $lockedBooking->id,
                'changed_by_user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'COMPANY_ACCEPTED',
                'notes' => $validated['reason']
                    ?? 'Driver declined the assigned job.',
                'metadata' => [
                    'driver_id' => $driver->id,
                    'source' => 'driver_app',
                    'action' => 'driver_declined',
                ],
            ]);
        });

        return response()->json([
            'message' => 'Job declined and returned to the company.',
            'data' => $booking->fresh([
                'company',
                'statusHistories',
            ]),
        ]);
    }

    public function startArriving(
        Request $request,
        Booking $booking
    ): JsonResponse {
        return $this->changeStatus(
            request: $request,
            booking: $booking,
            requiredStatus: 'DRIVER_ACCEPTED',
            newStatus: 'DRIVER_ARRIVING',
            message: 'Driver is now heading to the pickup location.',
            notes: 'Driver started travelling to the airport pickup point.'
        );
    }

    public function arrived(
        Request $request,
        Booking $booking
    ): JsonResponse {
        return $this->changeStatus(
            request: $request,
            booking: $booking,
            requiredStatus: 'DRIVER_ARRIVING',
            newStatus: 'DRIVER_ARRIVED',
            message: 'Driver arrival recorded successfully.',
            notes: 'Driver arrived at the airport pickup location.'
        );
    }

    public function start(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $validated = $this->validateDriver($request);

        if ((int) $booking->driver_id !== (int) $validated['driver_id']) {
            return response()->json([
                'message' => 'This booking is not assigned to this driver.',
            ], 403);
        }

        if ($booking->status !== 'DRIVER_ARRIVED') {
            return response()->json([
                'message' => 'The trip can only start after the driver arrives.',
                'current_status' => $booking->status,
            ], 409);
        }

        DB::transaction(function () use ($booking): void {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => 'IN_PROGRESS',
                'started_at' => now(),
            ]);

            BookingStatusHistory::query()->create([
                'booking_id' => $booking->id,
                'changed_by_user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'IN_PROGRESS',
                'notes' => 'Passenger boarded and the trip started.',
                'metadata' => [
                    'driver_id' => $booking->driver_id,
                    'source' => 'driver_app',
                ],
            ]);
        });

        return response()->json([
            'message' => 'Trip started successfully.',
            'data' => $booking->fresh([
                'traveller',
                'rideType',
                'driver',
                'vehicle',
                'statusHistories',
            ]),
        ]);
    }

    public function complete(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $validated = $this->validateDriver($request);

        if ((int) $booking->driver_id !== (int) $validated['driver_id']) {
            return response()->json([
                'message' => 'This booking is not assigned to this driver.',
            ], 403);
        }

        if ($booking->status !== 'IN_PROGRESS') {
            return response()->json([
                'message' => 'Only an active trip can be completed.',
                'current_status' => $booking->status,
            ], 409);
        }

        DB::transaction(function () use ($booking): void {
            $lockedBooking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $driver = $lockedBooking->driver;
            $vehicle = $lockedBooking->vehicle;
            $oldStatus = $lockedBooking->status;

            $lockedBooking->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ]);

            if ($driver) {
                $driver->update([
                    'status' => 'AVAILABLE',
                ]);
            }

            if ($vehicle) {
                $vehicle->update([
                    'status' => 'AVAILABLE',
                ]);
            }

            BookingStatusHistory::query()->create([
                'booking_id' => $lockedBooking->id,
                'changed_by_user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'COMPLETED',
                'notes' => 'Driver completed the trip.',
                'metadata' => [
                    'driver_id' => $lockedBooking->driver_id,
                    'vehicle_id' => $lockedBooking->vehicle_id,
                    'source' => 'driver_app',
                ],
            ]);
        });

        return response()->json([
            'message' => 'Trip completed successfully.',
            'data' => $booking->fresh([
                'traveller',
                'rideType',
                'company',
                'driver',
                'vehicle',
                'statusHistories',
            ]),
        ]);
    }

    private function changeStatus(
        Request $request,
        Booking $booking,
        string $requiredStatus,
        string $newStatus,
        string $message,
        string $notes
    ): JsonResponse {
        $validated = $this->validateDriver($request);

        if ((int) $booking->driver_id !== (int) $validated['driver_id']) {
            return response()->json([
                'message' => 'This booking is not assigned to this driver.',
            ], 403);
        }

        if ($booking->status !== $requiredStatus) {
            return response()->json([
                'message' => 'This action is not allowed for the current trip status.',
                'current_status' => $booking->status,
                'required_status' => $requiredStatus,
            ], 409);
        }

        DB::transaction(function () use (
            $booking,
            $newStatus,
            $notes
        ): void {
            $oldStatus = $booking->status;

            $booking->update([
                'status' => $newStatus,
            ]);

            BookingStatusHistory::query()->create([
                'booking_id' => $booking->id,
                'changed_by_user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
                'metadata' => [
                    'driver_id' => $booking->driver_id,
                    'source' => 'driver_app',
                ],
            ]);
        });

        return response()->json([
            'message' => $message,
            'data' => $booking->fresh([
                'traveller',
                'rideType',
                'driver',
                'vehicle',
                'statusHistories',
            ]),
        ]);
    }

    private function validateDriver(Request $request): array
    {
        return $request->validate([
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('drivers', 'id'),
            ],
        ]);
    }
}