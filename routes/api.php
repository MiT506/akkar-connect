<?php

use App\Http\Controllers\Api\V1\RideOptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/ride-options', [RideOptionController::class, 'index']);
});