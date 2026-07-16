<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function dashboard()
    {
        // Estadísticas reales basadas en la base de datos
        $totalStudents = User::where('role', 'student')->count();
        $recentStudents = User::where('role', 'student')->latest()->take(5)->get();
        $totalModules = \App\Models\Module::count();

        return Inertia::render('Teacher/Dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                // Módulos reales
                'active_courses' => $totalModules,
                'pending_reviews' => 5,
            ],
            'recent_students' => $recentStudents
        ]);
    }
}
