<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            SecurityAuditLogger::logInfo(
                'EMAIL_VERIFIED',
                "El usuario verificó exitosamente su correo institucional: {$request->user()->email}",
                ['user_id' => $request->user()->id, 'email' => $request->user()->email],
                $request
            );
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
