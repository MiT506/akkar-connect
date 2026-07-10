<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RideType;
use Illuminate\Http\JsonResponse;

class RideOptionController extends Controller
{
    public function index(): JsonResponse
    {
        $rideTypes = RideType::query()
            ->where('is_active', true)
            ->orderBy('base_price_usd')
            ->get()
            ->map(function (RideType $rideType): array {
                return [
                    'id' => $rideType->id,
                    'code' => $rideType->code,
                    'label_en' => $rideType->label_en,
                    'label_ar' => $rideType->label_ar,
                    'description_en' => $rideType->description_en,
                    'description_ar' => $rideType->description_ar,
                    'service_mode' => $rideType->service_mode,
                    'vehicle_category' => $rideType->vehicle_category,
                    'seats' => $rideType->seat_capacity,
                    'price_usd' => $rideType->base_price_usd,
                    'price_per_km_usd' => $rideType->price_per_km_usd,
                    'eta_minutes' => $rideType->default_eta_minutes,
                ];
            });

        return response()->json([
            'airport' => [
                'code' => 'KYE',
                'name_en' => 'René Mouawad Airport',
                'name_ar' => 'مطار رينيه معوض',
            ],
            'data' => $rideTypes,
        ]);
    }
}