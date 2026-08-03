<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rejects_unregistered_or_invalid_domain_users(): void
    {
        $response = $this->post('/login', [
            'email' => 'hacker@externaldomain.org',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_authenticates_valid_institutional_user(): void
    {
        $user = User::factory()->create([
            'email' => 'estudiante.demo@mail.pucv.cl',
            'password' => bcrypt('StrongPassword123!'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'email' => 'estudiante.demo@mail.pucv.cl',
            'password' => 'StrongPassword123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_rate_limiting_locks_out_after_too_many_failed_login_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'bruteforce.target@mail.pucv.cl',
            'password' => bcrypt('RealPassword123!'),
        ]);

        // Attempt 5 bad logins
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'bruteforce.target@mail.pucv.cl',
                'password' => 'WrongPassword!',
            ]);
        }

        // 6th attempt should be throttled
        $response = $this->post('/login', [
            'email' => 'bruteforce.target@mail.pucv.cl',
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}
