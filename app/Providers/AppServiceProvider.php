<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
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
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiting();
        $this->configureEmailVerification();
    }

    /**
     * Configure custom institutional verification email.
     */
    protected function configureEmailVerification(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $userName = $notifiable->name ?? 'Estudiante PUCV';
            
            return (new MailMessage)
                ->subject('🔐 Verificación de Correo Institucional - CLab PUCV')
                ->greeting("¡Hola, {$userName}!")
                ->line('Te damos la bienvenida a **CLab PUCV**, la plataforma interactiva de aprendizaje de programación en C.')
                ->line('Para activar tu cuenta y validar tu correo institucional (@mail.pucv.cl), por favor confirma tu dirección haciendo clic en el siguiente botón:')
                ->action('Verificar mi Correo Electrónico', $url)
                ->line('⏱️ Este enlace de verificación tiene una validez de 60 minutos.')
                ->line('Si no has solicitado la creación de esta cuenta en CLab PUCV, puedes ignorar este correo de forma segura.')
                ->salutation("Saludos cordiales,\n**Equipo Docente y Tecnológico de CLab PUCV**");
        });
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
