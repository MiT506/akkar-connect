<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('transport_companies')
                ->cascadeOnDelete();

            $table->string('plate_number', 30)->unique();
            $table->string('vehicle_type', 30);
            $table->string('make', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->unsignedSmallInteger('year')->nullable();

            $table->unsignedTinyInteger('seat_capacity');
            $table->unsignedTinyInteger('luggage_capacity')->nullable();

            $table->string('status', 30)->default('AVAILABLE');

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('vehicle_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};