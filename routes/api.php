<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CartController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth-register');
    Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:auth-password-forgot');
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:auth-password-reset');

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

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::middleware(['auth:sanctum', 'throttle:cart'])->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('/items', [CartController::class, 'storeItem']);
    Route::patch('/items/{productVariant}', [CartController::class, 'updateItem']);
    Route::delete('/items/{productVariant}', [CartController::class, 'destroyItem']);
    Route::delete('/items', [CartController::class, 'clear']);
});
