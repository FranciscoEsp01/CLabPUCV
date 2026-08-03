<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limiter for C Sandbox execution: 15 per minute
        RateLimiter::for('sandbox-execute', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip())->response(function () {
                return response()->json([
                    'output' => "⚠️ [LÍMITE DE VELOCIDAD EXCEDIDO]\nHas realizado demasiadas ejecuciones de código en poco tiempo. Por favor, espera unos segundos antes de volver a compilar."
                ], 429);
            });
        });

        // Rate limiter for AI Tutor: 12 per minute
        RateLimiter::for('ai-tutor', function (Request $request) {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip())->response(function () {
                return response()->json([
                    'reply' => 'Has alcanzado el límite de consultas por minuto con el Tutor IA. Por favor espera un momento.'
                ], 429);
            });
        });

        // Rate limiter for file uploads: 10 per minute
        RateLimiter::for('material-upload', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
