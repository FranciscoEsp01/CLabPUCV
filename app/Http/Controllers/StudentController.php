<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Module;
use App\Models\Lesson;

class StudentController extends Controller
{
    public function dashboard()
    {
        $modules = Module::with('lessons')
            ->where('is_visible', true)
            ->where(function ($query) {
                $query->whereNull('available_from')
                      ->orWhere('available_from', '<=', now());
            })
            ->orderBy('order')
            ->get();
        
        return Inertia::render('Student/Dashboard', [
            'modules_data' => $modules
        ]);
    }

    public function showLesson(Lesson $lesson)
    {
        $lesson->load('module');
        
        // Verificar si el módulo es visible y está disponible
        $module = $lesson->module;
        if (!$module->is_visible || ($module->available_from && $module->available_from > now())) {
            abort(403, 'Esta lección aún no está disponible.');
        }

        return Inertia::render('Student/Lesson', [
            'lesson' => $lesson
        ]);
    }

    public function materials()
    {
        $materials = \App\Models\Material::orderBy('created_at', 'desc')->get();
        return Inertia::render('Student/Materials', [
            'materials' => $materials
        ]);
    }

    public function leaderboard()
    {
        $topStudents = \App\Models\User::where('role', 'student')
            ->orderBy('points', 'desc')
            ->take(10)
            ->get(['id', 'name', 'avatar', 'points']);
            
        return Inertia::render('Student/Leaderboard', [
            'topStudents' => $topStudents
        ]);
    }
}
