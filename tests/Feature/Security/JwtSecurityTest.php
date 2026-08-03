<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Security\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JwtSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = app(JwtService::class);
    }

    public function test_authenticated_web_session_user_can_obtain_jwt_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'estudiante@mail.pucv.cl',
            'role' => 'student',
        ]);

        $response = $this->actingAs($user)->postJson('/api/auth/token');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'token_type',
                'access_token',
                'refresh_token',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role'],
            ]);

        $this->assertEquals('Bearer', $response->json('token_type'));
    }

    public function test_guest_can_authenticate_with_institutional_credentials_and_get_jwt(): void
    {
        $user = User::factory()->create([
            'email' => 'profesor@mail.pucv.cl',
            'password' => Hash::make('ClaveSegura123!'),
            'role' => 'teacher',
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => 'profesor@mail.pucv.cl',
            'password' => 'ClaveSegura123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'profesor@mail.pucv.cl',
                    'role' => 'teacher',
                ],
            ]);

        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_guest_cannot_obtain_jwt_with_non_institutional_email(): void
    {
        $response = $this->postJson('/api/auth/token', [
            'email' => 'hacker@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_guest_cannot_obtain_jwt_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'estudiante@mail.pucv.cl',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => 'estudiante@mail.pucv.cl',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_jwt_protected_route_allows_access_with_valid_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'alumno@mail.pucv.cl',
            'name' => 'Alumno PUCV',
            'role' => 'student',
        ]);

        $token = $this->jwtService->generateToken($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'alumno@mail.pucv.cl',
                    'name' => 'Alumno PUCV',
                    'role' => 'student',
                ],
            ]);
    }

    public function test_jwt_protected_route_rejects_missing_token(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_jwt_protected_route_rejects_tampered_token_signature(): void
    {
        $user = User::factory()->create([
            'email' => 'alumno@mail.pucv.cl',
            'role' => 'student',
        ]);

        $token = $this->jwtService->generateToken($user);
        
        // Tamper with payload (e.g. modify payload part)
        $parts = explode('.', $token);
        $payload = json_decode($this->jwtService->base64UrlDecode($parts[1]), true);
        $payload['role'] = 'admin'; // Privilege escalation attempt via forged payload
        $tamperedPayload = $this->jwtService->base64UrlEncode(json_encode($payload));
        $tamperedToken = "{$parts[0]}.{$tamperedPayload}.{$parts[2]}";

        $response = $this->withHeader('Authorization', "Bearer {$tamperedToken}")
            ->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_jwt_protected_route_rejects_expired_token(): void
    {
        $user = User::factory()->create([
            'email' => 'alumno@mail.pucv.cl',
        ]);

        // Generate token with negative TTL (already expired)
        $expiredToken = $this->jwtService->generateToken($user, -10);

        $response = $this->withHeader('Authorization', "Bearer {$expiredToken}")
            ->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_refresh_token_rotates_and_issues_new_token_pair(): void
    {
        $user = User::factory()->create([
            'email' => 'estudiante@mail.pucv.cl',
        ]);

        $refreshToken = $this->jwtService->generateRefreshToken($user);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'access_token',
                'refresh_token',
                'expires_in',
            ]);

        // The old refresh token should now be revoked/invalid
        $oldTokenValidation = $this->jwtService->validateToken($refreshToken, 'refresh');
        $this->assertNull($oldTokenValidation);
    }

    public function test_revoked_token_is_rejected_after_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'estudiante@mail.pucv.cl',
        ]);

        $token = $this->jwtService->generateToken($user);

        // First request is authorized
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(200);

        // Perform logout with token
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        // Second request with same token must be rejected (revoked in cache blacklist)
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
