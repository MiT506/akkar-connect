<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyDriverController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                Rule::exists('transport_companies', 'id'),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'AVAILABLE',
                    'ASSIGNED',
                    'ON_JOB',
                    'OFFLINE',
                ]),
            ],
        ]);

        $drivers = Driver::query()
            ->with([
                'vehicle:id,company_id,plate_number,vehicle_type,make,model,seat_capacity,status',
            ])
            ->where('company_id', $validated['company_id'])
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where(
                    'status',
                    $validated['status']
                )
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (Driver $driver): array {
                return [
                    'id' => $driver->id,
                    'name' => trim(
                        $driver->first_name . ' ' . $driver->last_name
                    ),
                    'phone' => $driver->phone,
                    'license_number' => $driver->license_number,
                    'rating' => $driver->rating,
                    'status' => $driver->status,
                    'vehicle' => $driver->vehicle
                        ? [
                            'id' => $driver->vehicle->id,
                            'plate_number' => $driver->vehicle->plate_number,
                            'vehicle_type' => $driver->vehicle->vehicle_type,
                            'make' => $driver->vehicle->make,
                            'model' => $driver->vehicle->model,
                            'seat_capacity' => $driver->vehicle->seat_capacity,
                            'status' => $driver->vehicle->status,
                        ]
                        : null,
                ];
            });

        return response()->json([
            'data' => $drivers,
        ]);
    }
}