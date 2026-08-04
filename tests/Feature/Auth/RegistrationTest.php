<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_with_institutional_email_and_receive_verification_email(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Estudiante PUCV',
            'email' => 'estudiante.test@mail.pucv.cl',
            'password' => 'password123#',
            'password_confirmation' => 'password123#',
        ]);

        $this->assertAuthenticated();
        
        $user = User::where('email', 'estudiante.test@mail.pucv.cl')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_cannot_register_with_non_institutional_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Attacker User',
            'email' => 'attacker@gmail.com',
            'password' => 'password123#',
            'password_confirmation' => 'password123#',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_unverified_user_is_redirected_to_verification_prompt_when_accessing_app(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'nuevo.alumno@mail.pucv.cl',
        ]);

        $response = $this->actingAs($user)->get('/app/dashboard');
        $response->assertRedirect(route('verification.notice'));

        $sandboxResponse = $this->actingAs($user)->get('/app/sandbox');
        $sandboxResponse->assertRedirect(route('verification.notice'));
    }
}
