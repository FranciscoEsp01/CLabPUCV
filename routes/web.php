<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rutas de Autenticación con Google
Route::middleware('guest')->group(function () {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Asistente IA
    Route::post('/api/ai-tutor/chat', [\App\Http\Controllers\AiTutorController::class, 'chat'])->name('ai.chat');
});

// ==========================================
// ZONA DE ESTUDIANTES
// ==========================================
// Nota: 'role:student' middleware debe ser creado o manejado mediante un Gate/Policy
Route::middleware(['auth'])->prefix('app')->name('student.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Student/Dashboard');
    })->name('dashboard');
    
    Route::get('/leaderboard', function () {
        return Inertia::render('Student/Leaderboard');
    })->name('leaderboard');
    
    Route::get('/sandbox', function () {
        return Inertia::render('Student/Sandbox');
    })->name('sandbox.index');
    Route::post('/sandbox/execute', [\App\Http\Controllers\SandboxController::class, 'execute'])->name('sandbox.execute');

    Route::get('/lesson', function () {
        return Inertia::render('Student/Lesson');
    })->name('lesson');
});

// ==========================================
// ZONA DE PROFESORES
// ==========================================
Route::middleware(['auth', 'role:teacher,admin'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\TeacherController::class, 'dashboard'])->name('dashboard');
});

// ==========================================
// ZONA AUTENTICACIÓN GOOGLE
// ==========================================
Route::get('/auth/google', [\App\Http\Controllers\GoogleLoginController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleLoginController::class, 'callback'])->name('google.callback');

require __DIR__.'/auth.php';
