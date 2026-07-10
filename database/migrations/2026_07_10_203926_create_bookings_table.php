<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('traveller_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('ride_type_id')
                ->constrained('ride_types')
                ->restrictOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('transport_companies')
                ->nullOnDelete();

            $table->foreignId('driver_id')
                ->nullable()
                ->constrained('drivers')
                ->nullOnDelete();

            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained('vehicles')
                ->nullOnDelete();

            // René Mouawad Airport is fixed, so no airport_id is needed.
            $table->string('flight_number', 30);
            $table->timestampTz('scheduled_arrival_at');

            $table->unsignedTinyInteger('party_size');
            $table->string('luggage_size', 20);

            $table->string('pickup_terminal', 100)
                ->default('Main Terminal');

            $table->string('destination_name', 255);

            $table->decimal('quoted_price_usd', 10, 2);
            $table->decimal('quoted_price_lbp', 15, 2)->nullable();

            $table->string('status', 40)->default('PENDING');

            $table->timestampTz('company_accepted_at')->nullable();
            $table->timestampTz('driver_assigned_at')->nullable();
            $table->timestampTz('driver_accepted_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();

            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['traveller_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index(['flight_number', 'scheduled_arrival_at']);
        });

        DB::statement("
            ALTER TABLE bookings
            ADD COLUMN destination_location geometry(Point, 4326) NOT NULL
        ");

        DB::statement("
            CREATE INDEX bookings_destination_location_gix
            ON bookings
            USING GIST (destination_location)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};