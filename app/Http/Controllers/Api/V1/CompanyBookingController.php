<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Driver;
use App\Models\TransportCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'PENDING',
                    'SEARCHING_COMPANY',
                    'COMPANY_OFFERED',
                    'COMPANY_ACCEPTED',
                    'DRIVER_ASSIGNED',
                    'DRIVER_ACCEPTED',
                    'DRIVER_ARRIVING',
                    'DRIVER_ARRIVED',
                    'IN_PROGRESS',
                    'COMPLETED',
                    'CANCELLED',
                    'DECLINED',
                    'EXPIRED',
                ]),
            ],
        ]);

        $bookings = Booking::query()
            ->with([
                'traveller:id,name,email,phone',
                'rideType:id,code,label_en,label_ar,vehicle_category',
                'company:id,name',
                'driver',
                'vehicle',
            ])
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where(
                    'status',
                    $validated['status']
                )
            )
            ->whereIn('status', [
                'PENDING',
                'SEARCHING_COMPANY',
                'COMPANY_OFFERED',
                'COMPANY_ACCEPTED',
                'DRIVER_ASSIGNED',
                'DRIVER_ACCEPTED',
                'DRIVER_ARRIVING',
                'DRIVER_ARRIVED',
                'IN_PROGRESS',
            ])
            ->latest()
            ->get();

        return response()->json([
            'data' => $bookings,
        ]);
    }

    public function accept(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                Rule::exists('transport_companies', 'id')
                    ->where('status', 'ACTIVE'),
            ],
        ]);

        if (!in_array($booking->status, [
            'PENDING',
            'SEARCHING_COMPANY',
            'COMPANY_OFFERED',
        ], true)) {
            return response()->json([
                'message' => 'This booking can no longer be accepted.',
                'current_status' => $booking->status,
            ], 409);
        }

        $company = TransportCompany::query()
            ->findOrFail($validated['company_id']);

        DB::transaction(function () use ($booking, $company): void {
            $oldStatus = $booking->status;

            $booking->update([
                'company_id' => $company->id,
                'status' => 'COMPANY_ACCEPTED',
                'company_accepted_at' => now(),
            ]);

            BookingStatusHistory::query()->create([
                'booking_id' => $booking->id,
                'changed_by_user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'COMPANY_ACCEPTED',
                'notes' => 'Booking accepted by transport company.',
                'metadata' => [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'source' => 'company_portal',
                ],
            ]);
        });

        return response()->json([
            'message' => 'Booking accepted successfully.',
            'data' => $booking->fresh([
                'traveller',
                'rideType',
                'company',
                'statusHistories',
            ]),
        ]);
    }

    public function decline(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                Rule::exists('transport_companies', 'id'),
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        if (!in_array($booking->status, [
            'PENDING',
            'SEARCHING_COMPANY',
            'COMPANY_OFFERED',
        ], true)) {
            return response()->json([
                'message' => 'This booking can no longer be declined.',
                'current_status' => $booking->status,
            ], 409);
        }

        $oldStatus = $booking->status;

        DB::transaction(function () use (
            $booking,
            $validated,
            $oldStatus
        ): void {
            $booking->update([
                'status' => 'DECLINED',
            ]);

            BookingStatusHistory::query()->create([
                'booking_id' => $booking->id,
                'changed_by_user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'DECLINED',
                'notes' => $validated['reason']
                    ?? 'Booking declined by transport company.',
                'metadata' => [
                    'company_id' => $validated['company_id'],
                    'source' => 'company_portal',
                ],
            ]);
        });

        return response()->json([
            'message' => 'Booking declined successfully.',
            'data' => $booking->fresh('statusHistories'),
        ]);
    }

    public function assignDriver(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                Rule::exists('transport_companies', 'id'),
            ],
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('drivers', 'id'),
            ],
        ]);

        if ($booking->status !== 'COMPANY_ACCEPTED') {
            return response()->json([
                'message' => 'A driver can only be assigned after the company accepts the booking.',
                'current_status' => $booking->status,
            ], 409);
        }

        if ((int) $booking->company_id !== (int) $validated['company_id']) {
            return response()->json([
                'message' => 'This booking does not belong to that company.',
            ], 403);
        }

        $result = DB::transaction(function () use (
            $booking,
            $validated
        ): Booking {
            $lockedBooking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $driver = Driver::query()
                ->with('vehicle')
                ->lockForUpdate()
                ->findOrFail($validated['driver_id']);

            if ((int) $driver->company_id !== (int) $validated['company_id']) {
                abort(403, 'The selected driver does not belong to this company.');
            }

            if ($driver->status !== 'AVAILABLE') {
                abort(409, 'The selected driver is not available.');
            }

            if (!$driver->vehicle) {
                abort(422, 'The selected driver has no assigned vehicle.');
            }

            if (
                (int) $driver->vehicle->company_id
                !== (int) $validated['company_id']
            ) {
                abort(403, 'The selected vehicle does not belong to this company.');
            }

            if ($driver->vehicle->status !== 'AVAILABLE') {
                abort(409, 'The selected vehicle is not available.');
            }

            $oldStatus = $lockedBooking->status;

            $lockedBooking->update([
                'driver_id' => $driver->id,
                'vehicle_id' => $driver->vehicle->id,
                'status' => 'DRIVER_ASSIGNED',
                'driver_assigned_at' => now(),
            ]);

            $driver->update([
                'status' => 'ASSIGNED',
            ]);

            $driver->vehicle->update([
                'status' => 'ASSIGNED',
            ]);

            BookingStatusHistory::query()->create([
                'booking_id' => $lockedBooking->id,
                'changed_by_user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'DRIVER_ASSIGNED',
                'notes' => 'Driver and vehicle assigned by company operator.',
                'metadata' => [
                    'company_id' => $validated['company_id'],
                    'driver_id' => $driver->id,
                    'driver_name' => trim(
                        $driver->first_name . ' ' . $driver->last_name
                    ),
                    'vehicle_id' => $driver->vehicle->id,
                    'plate_number' => $driver->vehicle->plate_number,
                    'source' => 'company_portal',
                ],
            ]);

            return $lockedBooking->fresh([
                'traveller',
                'rideType',
                'company',
                'driver',
                'vehicle',
                'statusHistories',
            ]);
        });

        return response()->json([
            'message' => 'Driver assigned successfully.',
            'data' => $result,
        ]);
    }
}