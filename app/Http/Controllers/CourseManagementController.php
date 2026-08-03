<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Module;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'available_from' => ['nullable', 'date'],
        ]);

        $module = Module::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
            'is_visible' => $validated['is_visible'] ?? true,
            'available_from' => $validated['available_from'] ?? null,
        ]);

        SecurityAuditLogger::logInfo(
            'MODULE_CREATED',
            "Módulo creado: {$module->title}",
            ['module_id' => $module->id],
            $request
        );

        return redirect()->back()->with('success', 'Módulo creado correctamente.');
    }

    public function updateModule(Request $request, Module $module)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'available_from' => ['nullable', 'date'],
        ]);

        $module->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? $module->order,
            'is_visible' => $validated['is_visible'] ?? $module->is_visible,
            'available_from' => $validated['available_from'] ?? null,
        ]);

        SecurityAuditLogger::logInfo(
            'MODULE_UPDATED',
            "Módulo actualizado: {$module->title}",
            ['module_id' => $module->id],
            $request
        );

        return redirect()->back()->with('success', 'Módulo actualizado correctamente.');
    }

    public function destroyModule(Module $module, Request $request)
    {
        $moduleTitle = $module->title;

        // Cleanup any attached PDFs in lessons belonging to this module
        foreach ($module->lessons as $lesson) {
            $this->deleteLessonPdf($lesson);
        }

        $module->delete();

        SecurityAuditLogger::logInfo(
            'MODULE_DELETED',
            "Módulo eliminado: {$moduleTitle}",
            ['module_id' => $module->id],
            $request
        );

        return redirect()->back()->with('success', 'Módulo eliminado correctamente.');
    }

    public function storeLesson(Request $request, Module $module)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $pdfPath = null;
        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('pdfs', 'public');
            $pdfPath = '/storage/' . $path;
        }

        $lesson = $module->lessons()->create([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
            'pdf_path' => $pdfPath,
            'order' => $validated['order'] ?? 0,
        ]);

        SecurityAuditLogger::logInfo(
            'LESSON_CREATED',
            "Lección creada: {$lesson->title} en módulo #{$module->id}",
            ['lesson_id' => $lesson->id, 'module_id' => $module->id],
            $request
        );

        return redirect()->back()->with('success', 'Lección agregada correctamente.');
    }

    public function updateLesson(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $updateData = [
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
            'order' => $validated['order'] ?? $lesson->order,
        ];

        if ($request->hasFile('pdf_file')) {
            // Delete old PDF from storage if exists
            $this->deleteLessonPdf($lesson);

            $path = $request->file('pdf_file')->store('pdfs', 'public');
            $updateData['pdf_path'] = '/storage/' . $path;
        }

        $lesson->update($updateData);

        SecurityAuditLogger::logInfo(
            'LESSON_UPDATED',
            "Lección actualizada: {$lesson->title}",
            ['lesson_id' => $lesson->id],
            $request
        );

        return redirect()->back()->with('success', 'Lección actualizada correctamente.');
    }

    public function destroyLesson(Lesson $lesson, Request $request)
    {
        $lessonTitle = $lesson->title;
        $this->deleteLessonPdf($lesson);
        $lesson->delete();

        SecurityAuditLogger::logInfo(
            'LESSON_DELETED',
            "Lección eliminada: {$lessonTitle}",
            ['lesson_id' => $lesson->id],
            $request
        );

        return redirect()->back()->with('success', 'Lección eliminada correctamente.');
    }

    /**
     * Helper to safely remove physical PDF associated with a lesson from public disk.
     */
    protected function deleteLessonPdf(Lesson $lesson): void
    {
        if (!empty($lesson->pdf_path)) {
            // pdf_path is stored as '/storage/pdfs/abc.pdf', relative path on public disk is 'pdfs/abc.pdf'
            $relativePath = ltrim(str_replace('/storage/', '', $lesson->pdf_path), '/');
            if ($relativePath && Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }
    }
}
