<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\Security\SecurityAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::with('user')->latest()->get();
        return Inertia::render('Teacher/Materials', [
            'materials' => $materials
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:documentation,exercise'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB max, strict PDF
        ]);

        $file = $request->file('file');

        // Deep inspection: verify PDF magic bytes (%PDF-) to prevent polyglot and spoofed files
        $header = @file_get_contents($file->getRealPath(), false, null, 0, 5);
        if ($header !== '%PDF-') {
            SecurityAuditLogger::logWarning(
                'FILE_MIME_SPOOF_ATTEMPT',
                "Intento de subida de archivo no-PDF con extensión falsa: {$file->getClientOriginalName()}",
                [
                    'client_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                ],
                $request
            );

            throw ValidationException::withMessages([
                'file' => 'El archivo proporcionado no es un documento PDF válido o está dañado.',
            ]);
        }

        $path = $file->store('materials', 'public');

        $material = Material::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'file_path' => $path,
            'user_id' => $request->user()->id,
        ]);

        SecurityAuditLogger::logInfo(
            'MATERIAL_UPLOADED',
            "Nuevo material pedagógico subido: {$material->title}",
            [
                'material_id' => $material->id,
                'file_path' => $path,
                'size_bytes' => $file->getSize(),
            ],
            $request
        );

        return redirect()->back()->with('success', 'Material subido correctamente.');
    }

    public function destroy(Material $material, Request $request)
    {
        $materialTitle = $material->title;
        $filePath = $material->file_path;

        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
        
        $material->delete();

        SecurityAuditLogger::logInfo(
            'MATERIAL_DELETED',
            "Material pedagógico eliminado: {$materialTitle}",
            [
                'material_id' => $material->id,
                'file_path' => $filePath,
            ],
            $request
        );

        return redirect()->back()->with('success', 'Material eliminado correctamente.');
    }
}
