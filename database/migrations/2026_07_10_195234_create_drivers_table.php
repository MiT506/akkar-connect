<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('company_id')
                ->constrained('transport_companies')
                ->cascadeOnDelete();

            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained('vehicles')
                ->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');

            $table->string('phone',30)->unique();

            $table->string('license_number')->unique();

            $table->decimal('rating',2,1)->default(5.0);

            $table->string('status')->default('OFFLINE');


            $table->timestamps();

            $table->index('status');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};