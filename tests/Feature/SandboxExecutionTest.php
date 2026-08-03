<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SandboxExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_sandbox_page(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'alumno@mail.pucv.cl',
        ]);

        $response = $this->actingAs($student)->get(route('student.sandbox.index'));

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_sandbox(): void
    {
        $response = $this->get(route('student.sandbox.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_can_execute_simple_c_code(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'alumno@mail.pucv.cl',
        ]);

        $code = <<<'C'
#include <stdio.h>

int main() {
    printf("Test Output PUCV\n");
    return 0;
}
C;

        $response = $this->actingAs($student)->postJson(route('student.sandbox.execute'), [
            'code' => $code,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['output']);
        $this->assertStringContainsString('Test Output PUCV', $response->json('output'));
    }

    public function test_student_can_execute_c_code_with_stdin(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'alumno@mail.pucv.cl',
        ]);

        $code = <<<'C'
#include <stdio.h>

int main() {
    int a, b;
    if (scanf("%d %d", &a, &b) == 2) {
        printf("Suma=%d\n", a + b);
    }
    return 0;
}
C;

        $response = $this->actingAs($student)->postJson(route('student.sandbox.execute'), [
            'code' => $code,
            'stdin' => '15 35',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Suma=50', $response->json('output'));
    }

    public function test_compilation_error_is_reported_gracefully(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'alumno@mail.pucv.cl',
        ]);

        $code = <<<'C'
#include <stdio.h>

int main() {
    this_is_a_syntax_error();
    return 0;
}
C;

        $response = $this->actingAs($student)->postJson(route('student.sandbox.execute'), [
            'code' => $code,
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Error de compilación', $response->json('output'));
    }

    public function test_dangerous_c_code_is_blocked_by_security_validator(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'alumno@mail.pucv.cl',
        ]);

        $code = <<<'C'
#include <stdio.h>
#include <stdlib.h>

int main() {
    system("rm -rf /");
    return 0;
}
C;

        $response = $this->actingAs($student)->postJson(route('student.sandbox.execute'), [
            'code' => $code,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('[BLOQUEO DE SEGURIDAD]', $response->json('output'));
    }
}
