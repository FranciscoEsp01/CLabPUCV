<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Redirige al usuario a la página de autenticación de Google.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Maneja la respuesta de Google tras la autenticación.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Validación estricta de dominio
            $allowedDomain = '@mail.pucv';
            if (!str_ends_with($googleUser->email, $allowedDomain)) {
                return redirect()->route('login')
                    ->with('error', 'El acceso está restringido exclusivamente a correos institucionales de la PUCV (@mail.pucv).');
            }

            $userExists = User::where('email', $googleUser->email)->exists();
            $studentRole = Role::firstOrCreate(['name' => 'student']);

            $user = User::updateOrCreate([
                'email' => $googleUser->email,
            ], [
                'name' => $googleUser->name,
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
                'role_id' => $userExists ? User::where('email', $googleUser->email)->first()->role_id : $studentRole->id, 
            ]);

            Auth::login($user);

            if ($user->isTeacher()) {
                return redirect()->route('teacher.dashboard');
            }

            return redirect()->route('dashboard');

        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Ocurrió un error durante la autenticación con Google.');
        }
    }
}
