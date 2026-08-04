<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        SecurityAuditLogger::logInfo(
            'EMAIL_VERIFICATION_RESENT',
            "Se reenvió el enlace de verificación a: {$request->user()->email}",
            ['user_id' => $request->user()->id, 'email' => $request->user()->email],
            $request
        );

        return back()->with('status', 'verification-link-sent');
    }
}
