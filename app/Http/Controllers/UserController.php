<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return Inertia::render('Teacher/Users', [
            'users' => $users
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $currentUser = Auth::user();

        // 1. Authorization check: Only administrators can modify roles
        if (!$currentUser || $currentUser->role !== 'admin') {
            SecurityAuditLogger::logViolation(
                'PRIVILEGE_ESCALATION_ATTEMPT',
                'Un usuario sin permisos de administrador intentó modificar el rol de otro usuario.',
                [
                    'target_user_id' => $user->id,
                    'attempted_role' => $request->input('role'),
                ],
                $request
            );

            abort(403, 'Acceso no autorizado. Se requieren privilegios de Administrador para modificar roles.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:student,teacher,admin'],
        ]);

        $newRole = $validated['role'];
        $oldRole = $user->role;

        // 2. Prevent demoting or locking out the last active administrator
        if ($oldRole === 'admin' && $newRole !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return redirect()->back()->withErrors([
                    'role' => 'Operación denegada: No es posible degradar al único administrador del sistema.'
                ]);
            }
        }

        // 3. Update the role
        $user->update([
            'role' => $newRole,
        ]);

        // 4. Security Audit Trail
        SecurityAuditLogger::logInfo(
            'USER_ROLE_CHANGED',
            "El usuario {$currentUser->email} cambió el rol de {$user->email} de '{$oldRole}' a '{$newRole}'.",
            [
                'admin_id' => $currentUser->id,
                'target_user_id' => $user->id,
                'target_email' => $user->email,
                'old_role' => $oldRole,
                'new_role' => $newRole,
            ],
            $request
        );

        return redirect()->back()->with('success', "Rol de {$user->name} actualizado a {$newRole} correctamente.");
    }

    /**
     * Delete a user permanently from the system.
     * EXCLUSIVELY available to administrators.
     */
    public function destroy(Request $request, User $user)
    {
        $currentUser = Auth::user();

        // 1. Authorization check: ONLY administrators can delete users
        if (!$currentUser || $currentUser->role !== 'admin') {
            SecurityAuditLogger::logViolation(
                'UNAUTHORIZED_USER_DELETION_ATTEMPT',
                'Un usuario sin privilegios de administrador intentó eliminar una cuenta de usuario.',
                [
                    'actor_id' => $currentUser?->id,
                    'actor_role' => $currentUser?->role,
                    'target_user_id' => $user->id,
                    'target_email' => $user->email,
                ],
                $request
            );

            abort(403, 'Acceso denegado. Se requieren privilegios exclusivos de Administrador para eliminar usuarios.');
        }

        // 2. Prevent self-deletion of currently logged-in administrator
        if ($currentUser->id === $user->id) {
            return redirect()->back()->withErrors([
                'user' => 'Operación denegada: No puedes eliminar tu propia cuenta de administrador en uso.'
            ]);
        }

        // 3. Prevent deleting the last active administrator
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return redirect()->back()->withErrors([
                    'user' => 'Operación denegada: No es posible eliminar al único administrador del sistema.'
                ]);
            }
        }

        $deletedId = $user->id;
        $deletedName = $user->name;
        $deletedEmail = $user->email;
        $deletedRole = $user->role;

        // 4. Safe transaction and storage cleanup
        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            // Delete any uploaded physical material files belonging to the user
            foreach ($user->hasMany(\App\Models\Material::class)->get() as $material) {
                if ($material->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($material->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($material->file_path);
                }
            }

            $user->delete();
        });

        // 5. Security Audit Log
        SecurityAuditLogger::logInfo(
            'USER_DELETED_BY_ADMIN',
            "El administrador {$currentUser->email} eliminó permanentemente la cuenta de {$deletedEmail} (ID: {$deletedId}, Rol: {$deletedRole}).",
            [
                'admin_id' => $currentUser->id,
                'deleted_user_id' => $deletedId,
                'deleted_name' => $deletedName,
                'deleted_email' => $deletedEmail,
                'deleted_role' => $deletedRole,
            ],
            $request
        );

        return redirect()->back()->with('success', "El usuario {$deletedName} ({$deletedEmail}) ha sido eliminado permanentemente del sistema.");
    }
}

