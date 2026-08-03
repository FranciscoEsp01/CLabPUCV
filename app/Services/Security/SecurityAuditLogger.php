<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SecurityAuditLogger
{
    /**
     * Log a security violation event.
     *
     * @param string $eventType e.g., 'SANDBOX_VIOLATION', 'AUTH_LOCKOUT', 'PRIVILEGE_ESCALATION_ATTEMPT', 'UNAUTHORIZED_ACCESS'
     * @param string $description
     * @param array $context
     * @param Request|null $request
     */
    public static function logViolation(string $eventType, string $description, array $context = [], ?Request $request = null): void
    {
        $request = $request ?? request();

        $payload = array_merge([
            'event' => $eventType,
            'description' => $description,
            'user_id' => auth()->id() ?? $context['user_id'] ?? null,
            'user_email' => auth()->user()?->email ?? $context['email'] ?? null,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::warning("[SECURITY AUDIT] [{$eventType}] {$description}", $payload);
    }

    /**
     * Alias for logViolation to handle warning level logs.
     */
    public static function logWarning(string $eventType, string $description, array $context = [], ?Request $request = null): void
    {
        self::logViolation($eventType, $description, $context, $request);
    }

    /**
     * Log a security informational event (e.g., successful login, role elevation, password change).
     *
     * @param string $eventType
     * @param string $description
     * @param array $context
     * @param Request|null $request
     */
    public static function logInfo(string $eventType, string $description, array $context = [], ?Request $request = null): void
    {
        $request = $request ?? request();

        $payload = array_merge([
            'event' => $eventType,
            'description' => $description,
            'user_id' => auth()->id() ?? $context['user_id'] ?? null,
            'user_email' => auth()->user()?->email ?? $context['email'] ?? null,
            'ip' => $request?->ip(),
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::info("[SECURITY AUDIT] [{$eventType}] {$description}", $payload);
    }
}
