<?php

use App\Http\Controllers\AiTutorController;
use App\Http\Controllers\Api\Auth\JwtAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CourseManagementController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SandboxController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==========================================
// AUTENTICACIÓN GOOGLE (OAuth 2.0)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

// ==========================================
// AUTENTICACIÓN Y GESTIÓN JWT (API / SPA)
// ==========================================
Route::prefix('api/auth')->group(function () {
    Route::post('/token', [JwtAuthController::class, 'issueToken'])->name('jwt.token');
    Route::post('/refresh', [JwtAuthController::class, 'refreshToken'])->name('jwt.refresh');
    
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/me', [JwtAuthController::class, 'me'])->name('jwt.me');
        Route::post('/logout', [JwtAuthController::class, 'logout'])->name('jwt.logout');
    });
});

// ==========================================
// VISTAS PÚBLICAS
// ==========================================
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// ==========================================
// REDIRECCIÓN DASHBOARD SEGÚN ROL
// ==========================================
Route::get('/dashboard', function () {
    if (auth()->check() && auth()->user()->isTeacher()) {
        return redirect()->route('teacher.dashboard');
    }
    return redirect()->route('student.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ==========================================
// RUTAS DE USUARIO AUTENTICADO GENERAL
// ==========================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Asistente IA (Tutor Inteligente de C) protegido con rate limiting
    Route::post('/api/ai-tutor/chat', [AiTutorController::class, 'chat'])
        ->middleware('throttle:ai-tutor')
        ->name('ai.chat');
});

// ==========================================
// ZONA DE ESTUDIANTES
// ==========================================
Route::middleware(['auth'])->prefix('app')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/leaderboard', [StudentController::class, 'leaderboard'])->name('leaderboard');
    
    Route::get('/sandbox', function () {
        return Inertia::render('Student/Sandbox');
    })->name('sandbox.index');
    
    Route::post('/sandbox/execute', [SandboxController::class, 'execute'])
        ->middleware('throttle:sandbox-execute')
        ->name('sandbox.execute');

    Route::get('/materials', [StudentController::class, 'materials'])->name('materials.index');
    Route::get('/lesson/{lesson}', [StudentController::class, 'showLesson'])->name('lesson.show');
});

// ==========================================
// ZONA DE PROFESORES Y ADMINISTRADORES
// ==========================================
Route::middleware(['auth', 'role:teacher,admin'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
    
    // Materiales de estudio
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
    Route::post('/materials', [MaterialController::class, 'store'])
        ->middleware('throttle:material-upload')
        ->name('materials.store');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');
    
    // Gestión de usuarios y roles
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    
    // Gestión de Cursos (Módulos y Lecciones)
    Route::get('/course-management', [CourseManagementController::class, 'index'])->name('course.index');
    Route::post('/modules', [CourseManagementController::class, 'storeModule'])->name('course.modules.store');
    Route::put('/modules/{module}', [CourseManagementController::class, 'updateModule'])->name('course.modules.update');
    Route::delete('/modules/{module}', [CourseManagementController::class, 'destroyModule'])->name('course.modules.destroy');
    
    Route::post('/modules/{module}/lessons', [CourseManagementController::class, 'storeLesson'])
        ->middleware('throttle:material-upload')
        ->name('course.lessons.store');
    Route::put('/lessons/{lesson}', [CourseManagementController::class, 'updateLesson'])
        ->middleware('throttle:material-upload')
        ->name('course.lessons.update');
    Route::delete('/lessons/{lesson}', [CourseManagementController::class, 'destroyLesson'])->name('course.lessons.destroy');
});

require __DIR__.'/auth.php';
