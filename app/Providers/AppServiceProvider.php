<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('verification-email-send', function (Request $request): array {
            $memberKey = (string) ($request->user()?->id ?? $request->ip());

            return [
                Limit::perMinute(1)->by('verification:email:send:cooldown:' . $memberKey),
                Limit::perMinutes(15, 5)->by('verification:email:send:burst:' . $memberKey),
                Limit::perDay(20)->by('verification:email:send:day:' . $memberKey),
                Limit::perHour(30)->by('verification:email:send:ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('verification-phone-send', function (Request $request): array {
            $phoneKey = (string) ($request->user()?->phone ?? $request->input('phone') ?? $request->ip());

            return [
                Limit::perMinute(1)->by('verification:phone:send:cooldown:' . $phoneKey),
                Limit::perMinutes(15, 3)->by('verification:phone:send:burst:' . $phoneKey),
                Limit::perDay(10)->by('verification:phone:send:day:' . $phoneKey),
                Limit::perHour(20)->by('verification:phone:send:ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('verification-email-verify', function (Request $request): array {
            $memberKey = (string) ($request->user()?->id ?? $request->ip());

            return [
                Limit::perMinute(10)->by('verification:email:verify:ip:' . $request->ip()),
                Limit::perMinutes(15, 30)->by('verification:email:verify:member:' . $memberKey),
            ];
        });

        RateLimiter::for('verification-phone-verify', function (Request $request): array {
            $phoneKey = (string) ($request->user()?->phone ?? $request->input('phone') ?? $request->ip());

            return [
                Limit::perMinutes(10, 5)->by('verification:phone:verify:phone:' . $phoneKey),
                Limit::perHour(30)->by('verification:phone:verify:ip:' . $request->ip()),
            ];
        });
    }
}
