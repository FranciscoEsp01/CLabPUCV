<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_access_teacher_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/teacher/dashboard');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_user_management(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/teacher/users');
        $response->assertStatus(403);
    }

    public function test_teacher_cannot_change_user_roles_only_admin_can(): void
    {
        $teacher = User::factory()->teacher()->create();
        $targetUser = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($teacher)->patch("/teacher/users/{$targetUser->id}/role", [
            'role' => 'teacher',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('student', $targetUser->fresh()->role);
    }

    public function test_admin_can_promote_student_to_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($admin)->patch("/teacher/users/{$targetUser->id}/role", [
            'role' => 'teacher',
        ]);

        $response->assertRedirect();
        $this->assertEquals('teacher', $targetUser->fresh()->role);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch("/teacher/users/{$admin->id}/role", [
            'role' => 'student',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertEquals('admin', $admin->fresh()->role);
    }
}
