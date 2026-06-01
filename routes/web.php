<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\FeeController as StudentFeeController;
use App\Http\Controllers\Student\HomeworkController as StudentHomeworkController;
use App\Http\Controllers\Student\MessageController as StudentMessageController;
use App\Http\Controllers\Tutor\BillController as TutorBillController;
use App\Http\Controllers\Tutor\FinanceController as TutorFinanceController;
use App\Http\Controllers\Tutor\HomeworkController as TutorHomeworkController;
use App\Http\Controllers\Tutor\MessageController as TutorMessageController;
use App\Http\Controllers\Tutor\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

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

        // Messages — tutor composes and views what they've sent (one-way in MVP).
        Route::resource('messages', TutorMessageController::class)->only(['index', 'create', 'store', 'show']);

        // Finance — fee setup, record payments, outstanding (per student).
        Route::get('finance', [TutorFinanceController::class, 'index'])->name('finance.index');
        Route::get('finance/{student}', [TutorFinanceController::class, 'show'])->name('finance.show');
        Route::put('finance/{student}/fee', [TutorFinanceController::class, 'saveFee'])->name('finance.fee.save');
        Route::post('finance/{student}/payments', [TutorFinanceController::class, 'storePayment'])->name('finance.payments.store');
        Route::delete('finance/payments/{payment}', [TutorFinanceController::class, 'destroyPayment'])->name('finance.payments.destroy');

        // WhatsApp Billing — generate, persist, and view bills.
        Route::get('billing', [TutorBillController::class, 'index'])->name('billing.index');
        Route::get('billing/create', [TutorBillController::class, 'create'])->name('billing.create');
        Route::post('billing', [TutorBillController::class, 'store'])->name('billing.store');
        Route::get('billing/{bill}', [TutorBillController::class, 'show'])->name('billing.show');
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

        // Messages — student inbox (read-only; cannot reply in MVP).
        Route::get('messages', [StudentMessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [StudentMessageController::class, 'show'])->name('messages.show');

        // Fees — parent unlock gate, then read-only fee page.
        Route::get('fees/unlock', [StudentFeeController::class, 'unlock'])->name('fees.unlock');
        Route::post('fees/unlock', [StudentFeeController::class, 'attemptUnlock'])->name('fees.unlock.attempt');
        Route::post('fees/lock', [StudentFeeController::class, 'lock'])->name('fees.lock');
        Route::get('fees', [StudentFeeController::class, 'index'])->middleware('fee.unlocked')->name('fees.index');
    });

require __DIR__.'/auth.php';
