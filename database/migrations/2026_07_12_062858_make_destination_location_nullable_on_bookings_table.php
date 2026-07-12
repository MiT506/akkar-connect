<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE bookings
             ALTER COLUMN destination_location DROP NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE bookings
             ALTER COLUMN destination_location SET NOT NULL'
        );
    }
};