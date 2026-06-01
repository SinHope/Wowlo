<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\HomeworkController as StudentHomeworkController;
use App\Http\Controllers\Tutor\HomeworkController as TutorHomeworkController;
use App\Http\Controllers\Tutor\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth (login only — accounts are created by the tutor)
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Tutor-only area — RoleMiddleware blocks students from every route here.
Route::middleware(['auth', 'verified', 'role:tutor'])
    ->prefix('tutor')
    ->name('tutor.')
    ->group(function () {
        Route::resource('students', StudentController::class)->except(['show']);

        // Homework status overview (must be declared before the resource to avoid {homework} capture)
        Route::get('homework-status', [TutorHomeworkController::class, 'status'])->name('homework.status');
        Route::get('homework/{homework}/download', [TutorHomeworkController::class, 'download'])->name('homework.download');
        Route::resource('homework', TutorHomeworkController::class)->except(['show']);
    });

// Student-only area — RoleMiddleware blocks tutors from these routes.
Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('homework', [StudentHomeworkController::class, 'index'])->name('homework.index');
        Route::patch('homework/{homework}/toggle', [StudentHomeworkController::class, 'toggle'])->name('homework.toggle');
        Route::get('homework/{homework}/download', [StudentHomeworkController::class, 'download'])->name('homework.download');
        Route::get('homework/{homework}', [StudentHomeworkController::class, 'show'])->name('homework.show');
    });

require __DIR__.'/auth.php';
