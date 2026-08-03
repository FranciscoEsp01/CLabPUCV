<?php

namespace App\Http\Middleware;

use App\Services\Security\JwtService;
use App\Services\Security\SecurityAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request authenticated via JSON Web Token (JWT).
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles Optional allowed roles (e.g., 'teacher', 'admin')
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            SecurityAuditLogger::logViolation(
                'JWT_AUTH_MISSING',
                'Petición a ruta protegida con JWT sin token de autorización',
                ['path' => $request->path()],
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'Acceso no autorizado: Token JWT no proporcionado.',
            ], 401);
        }

        $payload = $this->jwtService->validateToken($token, 'access');

        if (!$payload) {
            SecurityAuditLogger::logViolation(
                'JWT_AUTH_INVALID',
                'Intento de autenticación con token JWT inválido, expirado o revocado',
                ['token_snippet' => substr($token, 0, 15) . '...'],
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'Acceso no autorizado: Token JWT inválido, expirado o revocado.',
            ], 401);
        }

        $user = $this->jwtService->getUserFromToken($token);

        if (!$user) {
            SecurityAuditLogger::logViolation(
                'JWT_USER_NOT_FOUND',
                'Token JWT válido pero usuario no encontrado en la base de datos',
                ['user_id' => $payload['sub'] ?? null],
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 401);
        }

        // Check role authorization if specified
        if (!empty($roles) && !in_array($user->role, $roles)) {
            SecurityAuditLogger::logViolation(
                'JWT_FORBIDDEN_ROLE',
                "El usuario con rol [{$user->role}] intentó acceder a recurso que requiere roles [" . implode(',', $roles) . ']',
                ['user_id' => $user->id, 'email' => $user->email],
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: Permisos insuficientes para este recurso.',
            ], 403);
        }

        // Authenticate the user for the current lifecycle
        auth()->setUser($user);
        $request->attributes->set('jwt_payload', $payload);
        $request->attributes->set('jwt_token', $token);

        return $next($request);
    }

    /**
     * Extract the JWT token from the Authorization header, X-Access-Token, or cookie.
     */
    protected function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        $xToken = $request->header('X-Access-Token');
        if ($xToken) {
            return trim($xToken);
        }

        return $request->cookie('jwt_token');
    }
}
