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
    Route::get('/dashboard', [\App\Http\Controllers\StudentController::class, 'dashboard'])->name('dashboard');
    
    Route::get('/leaderboard', [\App\Http\Controllers\StudentController::class, 'leaderboard'])->name('leaderboard');
    
    Route::get('/sandbox', function () {
        return Inertia::render('Student/Sandbox');
    })->name('sandbox.index');
    Route::post('/sandbox/execute', [\App\Http\Controllers\SandboxController::class, 'execute'])->name('sandbox.execute');

    Route::get('/materials', [\App\Http\Controllers\StudentController::class, 'materials'])->name('materials.index');
    Route::get('/lesson/{lesson}', [\App\Http\Controllers\StudentController::class, 'showLesson'])->name('lesson.show');
});

// ==========================================
// ZONA DE PROFESORES
// ==========================================
Route::middleware(['auth', 'role:teacher,admin'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\TeacherController::class, 'dashboard'])->name('dashboard');
    Route::get('/materials', [\App\Http\Controllers\MaterialController::class, 'index'])->name('materials.index');
    Route::post('/materials', [\App\Http\Controllers\MaterialController::class, 'store'])->name('materials.store');
    Route::delete('/materials/{material}', [\App\Http\Controllers\MaterialController::class, 'destroy'])->name('materials.destroy');
    
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [\App\Http\Controllers\UserController::class, 'updateRole'])->name('users.updateRole');
    
    // Course Management
    Route::get('/course-management', [\App\Http\Controllers\CourseManagementController::class, 'index'])->name('course.index');
    Route::post('/modules', [\App\Http\Controllers\CourseManagementController::class, 'storeModule'])->name('course.modules.store');
    Route::put('/modules/{module}', [\App\Http\Controllers\CourseManagementController::class, 'updateModule'])->name('course.modules.update');
    Route::delete('/modules/{module}', [\App\Http\Controllers\CourseManagementController::class, 'destroyModule'])->name('course.modules.destroy');
    
    Route::post('/modules/{module}/lessons', [\App\Http\Controllers\CourseManagementController::class, 'storeLesson'])->name('course.lessons.store');
    Route::put('/lessons/{lesson}', [\App\Http\Controllers\CourseManagementController::class, 'updateLesson'])->name('course.lessons.update');
    Route::delete('/lessons/{lesson}', [\App\Http\Controllers\CourseManagementController::class, 'destroyLesson'])->name('course.lessons.destroy');
});

// ==========================================
// ZONA AUTENTICACIÓN GOOGLE
// ==========================================
Route::get('/auth/google', [\App\Http\Controllers\GoogleLoginController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleLoginController::class, 'callback'])->name('google.callback');

require __DIR__.'/auth.php';
