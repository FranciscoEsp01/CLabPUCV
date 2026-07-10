<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
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
        $request->validate([
            'role' => 'required|in:student,teacher,admin'
        ]);

        $user->update([
            'role' => $request->role
        ]);

        return redirect()->back()->with('success', 'Rol de usuario actualizado correctamente.');
    }
}
