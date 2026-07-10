<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;
use App\Models\Lesson;
use Inertia\Inertia;

class CourseManagementController extends Controller
{
    public function index()
    {
        $modules = Module::with('lessons')->orderBy('order')->get();
        return Inertia::render('Teacher/CourseManagement', [
            'modules' => $modules
        ]);
    }

    public function storeModule(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'integer'
        ]);

        Module::create($request->all());

        return redirect()->back()->with('success', 'Módulo creado correctamente.');
    }

    public function updateModule(Request $request, Module $module)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'integer'
        ]);

        $module->update($request->all());

        return redirect()->back()->with('success', 'Módulo actualizado correctamente.');
    }

    public function destroyModule(Module $module)
    {
        $module->delete();
        return redirect()->back()->with('success', 'Módulo eliminado correctamente.');
    }

    public function storeLesson(Request $request, Module $module)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'order' => 'integer'
        ]);

        $module->lessons()->create($request->all());

        return redirect()->back()->with('success', 'Lección agregada correctamente.');
    }

    public function updateLesson(Request $request, Lesson $lesson)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string',
            'order' => 'integer'
        ]);

        $lesson->update($request->all());

        return redirect()->back()->with('success', 'Lección actualizada correctamente.');
    }

    public function destroyLesson(Lesson $lesson)
    {
        $lesson->delete();
        return redirect()->back()->with('success', 'Lección eliminada correctamente.');
    }
}
