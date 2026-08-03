<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTutorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.groq.key', 'gsk_mock_test_key');
        Config::set('services.groq.url', 'https://api.groq.com/openai/v1/chat/completions');
        Config::set('services.groq.model', 'llama-3.3-70b-versatile');
    }

    public function test_unauthenticated_user_cannot_access_ai_tutor(): void
    {
        $response = $this->postJson('/api/ai-tutor/chat', [
            'message' => '¿Qué es un puntero en C?',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_receive_response_from_groq(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Un puntero en C es una variable que almacena la dirección de memoria de otra variable.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson('/api/ai-tutor/chat', [
            'message' => '¿Qué es un puntero en C?',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'reply' => 'Un puntero en C es una variable que almacena la dirección de memoria de otra variable.',
            'provider' => 'Groq (Llama 3.3)',
        ]);
    }

    public function test_ai_tutor_falls_back_to_openai_if_groq_fails(): void
    {
        $user = User::factory()->create();

        Config::set('services.openai.key', 'sk-mock-openai-key');
        Config::set('services.openai.model', 'gpt-3.5-turbo');

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response(['error' => 'Rate limit exceeded'], 429),
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Respuesta desde OpenAI de respaldo.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson('/api/ai-tutor/chat', [
            'message' => 'Explícame memoria dinámica',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'reply' => 'Respuesta desde OpenAI de respaldo.',
            'provider' => 'OpenAI',
        ]);
    }

    public function test_ai_tutor_validates_input(): void
    {
        $user = User::factory()->create();

        // Empty message
        $response = $this->actingAs($user)->postJson('/api/ai-tutor/chat', [
            'message' => '',
        ]);
        $response->assertStatus(422);

        // Too long message (>1500 chars)
        $response = $this->actingAs($user)->postJson('/api/ai-tutor/chat', [
            'message' => str_repeat('A', 1501),
        ]);
        $response->assertStatus(422);
    }

    public function test_ai_tutor_handles_no_configured_keys(): void
    {
        $user = User::factory()->create();

        Config::set('services.groq.key', null);
        Config::set('services.openai.key', null);

        $response = $this->actingAs($user)->postJson('/api/ai-tutor/chat', [
            'message' => '¿Cómo usar malloc?',
        ]);

        $response->assertStatus(503);
    }
}
