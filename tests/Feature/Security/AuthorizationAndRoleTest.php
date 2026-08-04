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

    public function test_student_cannot_delete_user(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $targetUser = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->delete("/teacher/users/{$targetUser->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $targetUser->id]);
    }

    public function test_teacher_cannot_delete_user(): void
    {
        $teacher = User::factory()->teacher()->create();
        $targetUser = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($teacher)->delete("/teacher/users/{$targetUser->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $targetUser->id]);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($admin)->delete("/teacher/users/{$targetUser->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete("/teacher/users/{$admin->id}");
        $response->assertRedirect();
        $response->assertSessionHasErrors(['user']);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_last_remaining_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $anotherAdmin = User::factory()->admin()->create();

        // Si hay 2 admins, se puede borrar uno
        $response = $this->actingAs($admin)->delete("/teacher/users/{$anotherAdmin->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $anotherAdmin->id]);
    }
}
