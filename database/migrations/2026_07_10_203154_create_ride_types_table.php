<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_types', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();

            $table->string('label_en', 100);
            $table->string('label_ar', 100);

            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();

            $table->string('service_mode', 30);
            $table->string('vehicle_category', 30);

            $table->unsignedTinyInteger('seat_capacity');

            $table->decimal('base_price_usd', 10, 2);
            $table->decimal('price_per_km_usd', 10, 2)->default(0);

            $table->unsignedSmallInteger('default_eta_minutes')->default(10);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['service_mode', 'is_active']);
            $table->index('vehicle_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_types');
    }
};