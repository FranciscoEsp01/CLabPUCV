<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_with_institutional_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'estudiante.test@mail.pucv.cl',
            'password' => 'password123#',
            'password_confirmation' => 'password123#',
        ]);

        $this->assertAuthenticated();
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
}
