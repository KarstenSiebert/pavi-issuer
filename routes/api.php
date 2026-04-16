<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AgeVerificationController;

Route::prefix('age')->group(function () {

    Route::middleware(['verify.mobileclient', 'throttle:api'])->group(function () {        
        Route::post('/enable', [AgeVerificationController::class, 'enable'])->name('api.age.enable');        
    });
    
});

Route::middleware(['throttle:api'])->group(function () {

    Route::post('/leaf', [AgeVerificationController::class, 'leaf'])->name('api.leaf');
});