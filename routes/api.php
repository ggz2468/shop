<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\PasswordResetController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth-register');
    Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:auth-password-forgot');

    Route::middleware(['auth:sanctum', 'throttle:auth-session'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

Route::prefix('verifications')->group(function () {
    Route::post('/phone/send', [VerificationController::class, 'sendSmsVerificationCode'])
        ->middleware('throttle:verification-phone-send');
    Route::post('/phone/verify', [VerificationController::class, 'verifyPhone'])
        ->middleware('throttle:verification-phone-verify');

    Route::post('/email/send', [VerificationController::class, 'sendEmailVerificationLink'])
        ->middleware('throttle:verification-email-send');
    Route::post('/email/verify', [VerificationController::class, 'verifyEmail'])
        ->middleware('throttle:verification-email-verify');
});

Route::apiResource('/products', ProductController::class)
    ->middleware('throttle:products');
