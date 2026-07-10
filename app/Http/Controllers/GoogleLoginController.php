<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class GoogleLoginController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['email' => 'Hubo un error al iniciar sesión con Google. Inténtalo nuevamente.']);
        }

        // Validate domain
        if (!Str::endsWith($googleUser->email, '@mail.pucv.cl')) {
            return redirect('/login')->withErrors([
                'email' => 'Debes usar tu correo institucional (@mail.pucv.cl).'
            ]);
        }

        // Check if user already exists
        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            // Create user
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'password' => bcrypt(Str::random(24)),
                'role' => 'student', // Default role
            ]);
        }

        Auth::login($user);

        return redirect()->intended('/dashboard');
    }
}
