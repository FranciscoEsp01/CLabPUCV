<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JwtService
{
    protected string $secret;
    protected int $ttl;
    protected int $refreshTtl;
    protected string $issuer;
    protected string $audience;

    public function __construct()
    {
        $this->secret = (string) (config('jwt.secret') ?: config('app.key') ?: 'clabpucv-jwt-secret-key-32chars!');
        $this->ttl = (int) config('jwt.ttl', 60);
        $this->refreshTtl = (int) config('jwt.refresh_ttl', 10080);
        $this->issuer = (string) config('jwt.issuer', 'https://clab.pucv.cl');
        $this->audience = (string) config('jwt.audience', 'clabpucv-web');
    }

    /**
     * Generate an RFC 7519 compliant JSON Web Token (Access Token).
     *
     * @param User $user
     * @param int|null $ttlMinutes
     * @param array $customClaims
     * @return string
     */
    public function generateToken(User $user, ?int $ttlMinutes = null, array $customClaims = []): string
    {
        $now = time();
        $ttl = $ttlMinutes ?? $this->ttl;
        $exp = $now + ($ttl * 60);
        $jti = bin2hex(random_bytes(16));

        $payload = array_merge([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'sub' => (string) $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'jti' => $jti,
            'token_type' => 'access',
        ], $customClaims);

        return $this->encode($payload);
    }

    /**
     * Generate a Refresh Token for session continuation without re-entering credentials.
     *
     * @param User $user
     * @param int|null $ttlMinutes
     * @return string
     */
    public function generateRefreshToken(User $user, ?int $ttlMinutes = null): string
    {
        $now = time();
        $ttl = $ttlMinutes ?? $this->refreshTtl;
        $exp = $now + ($ttl * 60);
        $jti = bin2hex(random_bytes(16));

        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'sub' => (string) $user->id,
            'jti' => $jti,
            'token_type' => 'refresh',
        ];

        return $this->encode($payload);
    }

    /**
     * Encode header and payload into a signed JWT string.
     *
     * @param array $payload
     * @return string
     */
    public function encode(array $payload): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $this->secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Validate and decode a JWT string. Returns the payload array if valid, or null if invalid.
     *
     * @param string $token
     * @param string $expectedType ('access' or 'refresh')
     * @return array|null
     */
    public function validateToken(string $token, string $expectedType = 'access'): ?array
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // 1. Verify signature in constant time
        $expectedSignature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $this->secret, true);
        $providedSignature = $this->base64UrlDecode($signatureEncoded);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        // 2. Decode header
        $headerJson = $this->base64UrlDecode($headerEncoded);
        $header = json_decode($headerJson, true);
        if (!$header || ($header['alg'] ?? null) !== 'HS256' || ($header['typ'] ?? null) !== 'JWT') {
            return null;
        }

        // 3. Decode payload
        $payloadJson = $this->base64UrlDecode($payloadEncoded);
        $payload = json_decode($payloadJson, true);
        if (!$payload || !is_array($payload)) {
            return null;
        }

        $now = time();

        // 4. Check expiration (exp)
        if (isset($payload['exp']) && $now >= $payload['exp']) {
            return null;
        }

        // 5. Check not before (nbf)
        if (isset($payload['nbf']) && $now < $payload['nbf']) {
            return null;
        }

        // 6. Check token type
        if (isset($payload['token_type']) && $payload['token_type'] !== $expectedType) {
            return null;
        }

        // 7. Check if revoked (Blacklisted)
        if (isset($payload['jti']) && $this->isRevoked($payload['jti'])) {
            return null;
        }

        return $payload;
    }

    /**
     * Revoke (Blacklist) a token by its JTI until its expiration timestamp.
     *
     * @param string $token
     * @return bool
     */
    public function revokeToken(string $token): bool
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 3) {
            return false;
        }

        $payloadJson = $this->base64UrlDecode($parts[1]);
        $payload = json_decode($payloadJson, true);

        if (!$payload || !isset($payload['jti'])) {
            return false;
        }

        $jti = $payload['jti'];
        $exp = $payload['exp'] ?? (time() + 3600);
        $ttlSeconds = max(1, $exp - time());

        Cache::put("jwt_blacklist:{$jti}", true, $ttlSeconds);

        return true;
    }

    /**
     * Check if a JTI has been revoked/blacklisted.
     *
     * @param string $jti
     * @return bool
     */
    public function isRevoked(string $jti): bool
    {
        return Cache::has("jwt_blacklist:{$jti}");
    }

    /**
     * Retrieve the Eloquent User associated with a valid JWT token.
     *
     * @param string $token
     * @param string $expectedType
     * @return User|null
     */
    public function getUserFromToken(string $token, string $expectedType = 'access'): ?User
    {
        $payload = $this->validateToken($token, $expectedType);
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }

        return User::find($payload['sub']);
    }

    /**
     * Base64URL encode a string without trailing '='.
     */
    public function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Base64URL decode a string with automatic padding restoration.
     */
    public function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
