<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\JwtService;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class JwtAuthController extends Controller
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Issue a JWT access & refresh token pair.
     * Supports both active session authentication and email/password login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function issueToken(Request $request): JsonResponse
    {
        // If the user is already authenticated in the web session, issue tokens directly
        if (Auth::check()) {
            $user = Auth::user();
            return $this->respondWithTokens($user, 'Token JWT emitido exitosamente para la sesión activa');
        }

        // Validate login credentials for direct API authentication
        $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'ends_with:@mail.pucv.cl',
            ],
            'password' => ['required', 'string'],
        ], [
            'email.ends_with' => 'El correo debe pertenecer al dominio institucional (@mail.pucv.cl).',
        ]);

        $throttleKey = 'jwt-login:' . Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            SecurityAuditLogger::logViolation(
                'JWT_AUTH_LOCKOUT',
                'Demasiados intentos fallidos de autenticación JWT',
                ['email' => $request->input('email'), 'seconds' => $seconds],
                $request
            );

            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos fallidos. Intente nuevamente en {$seconds} segundos.",
            ], 429);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 300);

            SecurityAuditLogger::logViolation(
                'JWT_LOGIN_FAILED',
                'Credenciales inválidas en intento de autenticación JWT',
                ['email' => $request->input('email')],
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        SecurityAuditLogger::logInfo(
            'JWT_LOGIN_SUCCESS',
            "Autenticación exitosa y emisión de token JWT para el usuario [{$user->email}]",
            ['user_id' => $user->id, 'role' => $user->role],
            $request
        );

        return $this->respondWithTokens($user, 'Autenticación exitosa');
    }

    /**
     * Refresh an expired or expiring access token using a valid refresh token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $refreshToken = $request->input('refresh_token');
        $payload = $this->jwtService->validateToken($refreshToken, 'refresh');

        if (!$payload || !isset($payload['sub'])) {
            SecurityAuditLogger::logViolation(
                'JWT_REFRESH_FAILED',
                'Intento de refresco con token de refresco inválido, expirado o revocado',
                [],
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'El token de actualización es inválido o ha expirado.',
            ], 401);
        }

        $user = User::find($payload['sub']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        // Revoke the used refresh token for rotation security
        $this->jwtService->revokeToken($refreshToken);

        SecurityAuditLogger::logInfo(
            'JWT_TOKEN_REFRESHED',
            "Tokens rotados y renovados exitosamente para el usuario [{$user->email}]",
            ['user_id' => $user->id],
            $request
        );

        return $this->respondWithTokens($user, 'Token actualizado correctamente');
    }

    /**
     * Get authenticated user profile and token claims.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->attributes->get('jwt_payload');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'points' => $user->points,
            ],
            'claims' => $payload,
        ]);
    }

    /**
     * Revoke the current access token (Blacklist).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('jwt_token');

        if ($token) {
            $this->jwtService->revokeToken($token);
        }

        SecurityAuditLogger::logInfo(
            'JWT_LOGOUT',
            'Token JWT revocado exitosamente por el usuario',
            ['user_id' => $request->user()?->id],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Sesión JWT cerrada y token revocado exitosamente.',
        ]);
    }

    /**
     * Build standard token response.
     */
    protected function respondWithTokens(User $user, string $message): JsonResponse
    {
        $accessToken = $this->jwtService->generateToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);
        $ttlSeconds = config('jwt.ttl', 60) * 60;

        return response()->json([
            'success' => true,
            'message' => $message,
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $ttlSeconds,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }
}
