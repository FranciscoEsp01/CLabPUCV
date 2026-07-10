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

        return Inertia::render('Teacher/Dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                // Agregaremos datos estáticos para lo que aún no tiene tabla en la BD
                'active_courses' => 2,
                'pending_reviews' => 5,
            ],
            'recent_students' => $recentStudents
        ]);
    }
}
