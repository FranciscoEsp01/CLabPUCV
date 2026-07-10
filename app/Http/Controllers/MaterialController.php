<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

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
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:documentation,exercise',
            'file' => 'required|mimes:pdf|max:10240', // 10MB max
        ]);

        $path = $request->file('file')->store('materials', 'public');

        Material::create([
            'title' => $request->title,
            'type' => $request->type,
            'file_path' => $path,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Material subido correctamente.');
    }

    public function destroy(Material $material)
    {
        if (Storage::disk('public')->exists($material->file_path)) {
            Storage::disk('public')->delete($material->file_path);
        }
        
        $material->delete();

        return redirect()->back()->with('success', 'Material eliminado correctamente.');
    }
}
