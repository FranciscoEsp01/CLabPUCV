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
        $modules = Module::with('lessons')->orderBy('order')->get();
        
        return Inertia::render('Student/Dashboard', [
            'modules_data' => $modules
        ]);
    }

    public function showLesson(Lesson $lesson)
    {
        $lesson->load('module');
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
}
