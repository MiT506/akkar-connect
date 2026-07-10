<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)
                ->nullable()
                ->unique()
                ->after('email');

            $table->string('role', 30)
                ->default('TRAVELLER')
                ->after('phone');

            $table->string('preferred_language', 2)
                ->default('en')
                ->after('role');

            $table->string('status', 30)
                ->default('ACTIVE')
                ->after('preferred_language');

            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
            $table->dropColumn([
                'phone',
                'role',
                'preferred_language',
                'status',
            ]);
        });
    }
};