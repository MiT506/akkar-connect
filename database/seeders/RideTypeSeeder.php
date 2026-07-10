<?php

namespace Database\Seeders;

use App\Models\RideType;
use Illuminate\Database\Seeder;

class RideTypeSeeder extends Seeder
{
    public function run(): void
    {
        RideType::updateOrCreate(
            ['code' => 'ECONOMY'],
            [
                'label_en' => 'Economy',
                'label_ar' => 'اقتصادي',
                'description_en' => 'Affordable private ride for everyday travel.',
                'description_ar' => 'رحلة خاصة بسعر مناسب للتنقل اليومي.',
                'service_mode' => 'PRIVATE',
                'vehicle_category' => 'SEDAN',
                'seat_capacity' => 4,
                'base_price_usd' => 15.00,
                'price_per_km_usd' => 0.80,
                'default_eta_minutes' => 8,
                'is_active' => true,
            ]
        );

        RideType::updateOrCreate(
            ['code' => 'COMFORT'],
            [
                'label_en' => 'Comfort',
                'label_ar' => 'مريح',
                'description_en' => 'A more spacious private ride with added comfort.',
                'description_ar' => 'رحلة خاصة أكثر اتساعاً وراحة.',
                'service_mode' => 'PRIVATE',
                'vehicle_category' => 'COMFORT',
                'seat_capacity' => 4,
                'base_price_usd' => 22.00,
                'price_per_km_usd' => 1.10,
                'default_eta_minutes' => 10,
                'is_active' => true,
            ]
        );

        RideType::updateOrCreate(
            ['code' => 'SHARED_VAN'],
            [
                'label_en' => 'Shared Van',
                'label_ar' => 'فان مشترك',
                'description_en' => 'Shared airport transfer with other travellers heading to a similar area.',
                'description_ar' => 'نقل مشترك من المطار مع مسافرين متجهين إلى منطقة قريبة.',
                'service_mode' => 'SHARED',
                'vehicle_category' => 'VAN',
                'seat_capacity' => 7,
                'base_price_usd' => 8.00,
                'price_per_km_usd' => 0.35,
                'default_eta_minutes' => 15,
                'is_active' => true,
            ]
        );

        RideType::updateOrCreate(
            ['code' => 'PRIVATE_TAXI'],
            [
                'label_en' => 'Private Taxi',
                'label_ar' => 'تاكسي خاص',
                'description_en' => 'Direct private airport transfer.',
                'description_ar' => 'نقل خاص ومباشر من المطار.',
                'service_mode' => 'PRIVATE',
                'vehicle_category' => 'TAXI',
                'seat_capacity' => 4,
                'base_price_usd' => 20.00,
                'price_per_km_usd' => 1.00,
                'default_eta_minutes' => 7,
                'is_active' => true,
            ]
        );
    }
}