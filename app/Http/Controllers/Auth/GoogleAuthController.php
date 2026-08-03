<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\SecurityAuditLogger;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the response from Google after authentication.
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception $e) {
            SecurityAuditLogger::logViolation(
                'OAUTH_GOOGLE_FAILURE',
                'Fallo durante el handshake o callback de Google OAuth.',
                ['error' => $e->getMessage()],
                $request
            );

            return redirect()->route('login')->withErrors([
                'email' => 'Ocurrió un error al autenticarse con Google. Por favor, inténtelo nuevamente.'
            ]);
        }

        $email = strtolower(trim($googleUser->getEmail() ?? ''));
        $allowedDomain = '@mail.pucv.cl';

        // Strict institutional domain check
        if (!str_ends_with($email, $allowedDomain)) {
            $existingUser = User::where('email', $email)->first();
            
            // Allow existing administrative users if any are explicitly pre-configured
            if (!$existingUser || !$existingUser->isAdmin()) {
                SecurityAuditLogger::logViolation(
                    'OAUTH_UNAUTHORIZED_DOMAIN',
                    "Intento de inicio de sesión con correo no institucional ({$email}).",
                    ['email' => $email],
                    $request
                );

                return redirect()->route('login')->withErrors([
                    'email' => 'El acceso está restringido exclusivamente a correos institucionales de la PUCV (@mail.pucv.cl).'
                ]);
            }
        }

        // Find or safely create user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?? 'Usuario PUCV',
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => bcrypt(Str::random(32)),
                'role' => 'student',
                'email_verified_at' => now(),
            ]);

            SecurityAuditLogger::logInfo(
                'USER_REGISTERED_OAUTH',
                "Nuevo usuario registrado vía Google OAuth: {$user->email}",
                ['user_id' => $user->id, 'email' => $user->email],
                $request
            );
        } else {
            // Update profile image / google_id if needed
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar() ?? $user->avatar,
            ]);
        }

        // Prevent Session Fixation
        Auth::login($user);
        $request->session()->regenerate();

        SecurityAuditLogger::logInfo(
            'AUTH_LOGIN_SUCCESS_OAUTH',
            "Inicio de sesión exitoso vía Google OAuth: {$user->email}",
            ['user_id' => $user->id, 'email' => $user->email, 'role' => $user->role],
            $request
        );

        if ($user->isTeacher()) {
            return redirect()->intended(route('teacher.dashboard'));
        }

        return redirect()->intended(route('student.dashboard'));
    }
}
