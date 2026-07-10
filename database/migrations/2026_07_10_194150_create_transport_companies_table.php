<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_companies', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('contact_phone', 30);
            $table->string('contact_email')->nullable();

            $table->string('status', 30)->default('ACTIVE');

            // How long an operator has to accept a dispatched request.
            $table->unsignedSmallInteger('dispatch_timeout_seconds')
                ->default(30);

            $table->timestamps();

            $table->index('status');
            $table->unique('contact_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_companies');
    }
};