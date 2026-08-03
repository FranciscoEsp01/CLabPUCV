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
}
