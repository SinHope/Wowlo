<?php

use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\TutorController as AdminTutorController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PatchNoteController;
use App\Http\Controllers\Admin\PatchNoteController as AdminPatchNoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Student\FeeController as StudentFeeController;
use App\Http\Controllers\Student\HomeworkController as StudentHomeworkController;
use App\Http\Controllers\Student\MessageController as StudentMessageController;
use App\Http\Controllers\Student\ExamPaperController as StudentExamPaperController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\SpellingController as StudentSpellingController;
use App\Http\Controllers\Student\MultiplicationController as StudentMultiplicationController;
use App\Http\Controllers\Tutor\BillController as TutorBillController;
use App\Http\Controllers\Tutor\ExamPaperController as TutorExamPaperController;
use App\Http\Controllers\Tutor\FinanceController as TutorFinanceController;
use App\Http\Controllers\Tutor\HomeworkController as TutorHomeworkController;
use App\Http\Controllers\Tutor\MessageController as TutorMessageController;
use App\Http\Controllers\Tutor\QuizController as TutorQuizController;
use App\Http\Controllers\Tutor\ResourceSheetController as TutorResourceSheetController;
use App\Http\Controllers\Tutor\SpellingController as TutorSpellingController;
use App\Http\Controllers\Tutor\MultiplicationController as TutorMultiplicationController;
use App\Http\Controllers\Tutor\StudentController;
use App\Http\Controllers\Student\ResourceSheetController as StudentResourceSheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::get('/about', function () {
    return view('about');
})->name('about');

// Public "How to use Wowlo" guide — the same walkthrough as the in-app
// onboarding tour, but shown inline on the page (Student / Tutor tabs).
Route::get('/how-to-use', function () {
    return view('how-to-use');
})->name('how-to-use');

// XML sitemap — the public, indexable marketing pages only (everything else is
// auth-gated and must stay out of the index). Built dynamically so it uses the
// current host (works on onrender.com today and a custom domain later).
Route::get('/sitemap.xml', function () {
    $pages = [
        ['loc' => url('/'),                'changefreq' => 'weekly',  'priority' => '1.0'],
        ['loc' => route('how-to-use'),     'changefreq' => 'monthly', 'priority' => '0.8'],
        ['loc' => route('about'),          'changefreq' => 'monthly', 'priority' => '0.7'],
        ['loc' => route('contact'),        'changefreq' => 'yearly',  'priority' => '0.5'],
        ['loc' => route('privacy-policy'), 'changefreq' => 'yearly',  'priority' => '0.3'],
    ];

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
    foreach ($pages as $p) {
        $xml .= "  <url>\n"
              . '    <loc>'.e($p['loc'])."</loc>\n"
              . '    <changefreq>'.$p['changefreq']."</changefreq>\n"
              . '    <priority>'.$p['priority']."</priority>\n"
              . "  </url>\n";
    }
    $xml .= '</urlset>'."\n";

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

// Public "Contact us" page. Submission is rate-limited against spam/abuse.
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])
    ->middleware('throttle:5,1')
    ->name('contact.send');

// Google OAuth (login only — accounts are created by the tutor)
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Games — placeholder page shared by tutors and students (feature coming soon).
Route::view('/games', 'games')
    ->middleware(['auth', 'verified'])
    ->name('games');

// Roll the Dice — purely a fun dice roll (no scoring, no data). Shared by all roles.
Route::view('/games/roll-the-dice', 'games.roll-the-dice')
    ->middleware(['auth', 'verified'])
    ->name('games.roll-the-dice');

// Patch Notes — public changelog any authenticated user can read. Writing is
// super_admin-only (see the admin group below).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/patch-notes', [PatchNoteController::class, 'index'])->name('patch-notes.index');
    Route::get('/patch-notes/{patchNote}/image', [PatchNoteController::class, 'image'])->name('patch-notes.image');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // PWA web-push subscription register/remove (best-effort enhancement).
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    // Mark the welcome tour as seen (called from the onboarding modal).
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
});

// Super-admin-only area — manage tutor accounts (no public sign-up yet).
Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('tutors', AdminTutorController::class)->except(['show']);

        // Banner Notifications — compose an app-wide announcement bar shown at
        // the top of the app to a chosen audience (everyone / tutors / students).
        Route::patch('banners/{banner}/toggle', [AdminBannerController::class, 'toggle'])->name('banners.toggle');
        Route::resource('banners', AdminBannerController::class)->except(['show']);

        // Patch Notes — author the public changelog (everyone reads, only the
        // super_admin writes). Reading lives in the shared group above.
        Route::get('patch-notes/create', [AdminPatchNoteController::class, 'create'])->name('patch-notes.create');
        Route::post('patch-notes', [AdminPatchNoteController::class, 'store'])->name('patch-notes.store');
        Route::get('patch-notes/{patchNote}/edit', [AdminPatchNoteController::class, 'edit'])->name('patch-notes.edit');
        Route::put('patch-notes/{patchNote}', [AdminPatchNoteController::class, 'update'])->name('patch-notes.update');
        Route::delete('patch-notes/{patchNote}', [AdminPatchNoteController::class, 'destroy'])->name('patch-notes.destroy');
    });

// Teaching workspace — tutors AND the super_admin (who teaches their own roster).
// RoleMiddleware blocks students from every route here.
Route::middleware(['auth', 'verified', 'role:tutor,super_admin'])
    ->prefix('tutor')
    ->name('tutor.')
    ->group(function () {
        Route::resource('students', StudentController::class)->except(['show']);

        // Homework status overview (must be declared before the resource to avoid {homework} capture)
        Route::get('homework-status', [TutorHomeworkController::class, 'status'])->name('homework.status');
        // Tutor's authoritative verdict (done / not_done) + feedback note.
        Route::patch('homework/{homework}/verdict', [TutorHomeworkController::class, 'verdict'])->name('homework.verdict');
        Route::get('homework/{homework}/download', [TutorHomeworkController::class, 'download'])->name('homework.download');
        Route::resource('homework', TutorHomeworkController::class)->except(['show']);

        // Messages — tutor composes/views what they've sent, plus a received
        // inbox (e.g. exam-paper approval notices from the super_admin).
        Route::get('messages/inbox', [TutorMessageController::class, 'inbox'])->name('messages.inbox');
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

        // Exam Papers — SHARED moderated library. Tutors submit (→ pending),
        // the super_admin approves/rejects; approved papers are visible to all.
        Route::get('exam-papers', [TutorExamPaperController::class, 'index'])->name('exam-papers.index');
        Route::get('exam-papers/create', [TutorExamPaperController::class, 'create'])->name('exam-papers.create');
        Route::post('exam-papers', [TutorExamPaperController::class, 'store'])->name('exam-papers.store');
        Route::post('exam-papers/{examPaper}/approve', [TutorExamPaperController::class, 'approve'])->name('exam-papers.approve');
        Route::post('exam-papers/{examPaper}/reject', [TutorExamPaperController::class, 'reject'])->name('exam-papers.reject');
        Route::delete('exam-papers/{examPaper}', [TutorExamPaperController::class, 'destroy'])->name('exam-papers.destroy');
        Route::get('exam-papers/{examPaper}/download', [TutorExamPaperController::class, 'download'])->name('exam-papers.download');

        // Quizzes — create MCQ + short-answer quizzes, assign, grade, view results.
        Route::get('quizzes', [TutorQuizController::class, 'index'])->name('quizzes.index');
        Route::get('quizzes/create', [TutorQuizController::class, 'create'])->name('quizzes.create');
        Route::post('quizzes', [TutorQuizController::class, 'store'])->name('quizzes.store');
        Route::get('quizzes/questions/{question}/image', [TutorQuizController::class, 'questionImage'])->name('quizzes.questions.image');
        // Per-answer tutor-feedback image (drawn explanation) — authorized stream.
        Route::get('quizzes/answers/{answer}/feedback-image', [TutorQuizController::class, 'feedbackImage'])->name('quizzes.answers.feedback-image');
        Route::get('quizzes/{quiz}', [TutorQuizController::class, 'show'])->name('quizzes.show');
        Route::get('quizzes/{quiz}/attempts/{attempt}', [TutorQuizController::class, 'attempt'])->name('quizzes.attempts.show');
        Route::post('quizzes/{quiz}/attempts/{attempt}/feedback', [TutorQuizController::class, 'feedback'])->name('quizzes.attempts.feedback');
        // Manual grading of short-answer submissions.
        Route::get('quizzes/{quiz}/attempts/{attempt}/grade', [TutorQuizController::class, 'grade'])->name('quizzes.attempts.grade');
        Route::post('quizzes/{quiz}/attempts/{attempt}/grade', [TutorQuizController::class, 'saveGrade'])->name('quizzes.attempts.grade.save');
        Route::post('quizzes/{quiz}/assign', [TutorQuizController::class, 'assign'])->name('quizzes.assign');
        Route::delete('quizzes/{quiz}', [TutorQuizController::class, 'destroy'])->name('quizzes.destroy');

        // Resources — MCQ/OAS + short-answer sheets. Tutor builds blank rows and
        // sends to one student; the student fills + submits; the tutor marks.
        Route::prefix('resources')->name('resources.')->group(function () {
            // {type} is mcq | short_answer (constraint stops it eating the sheets/* paths).
            Route::get('{type}', [TutorResourceSheetController::class, 'index'])->whereIn('type', ['mcq', 'short_answer'])->name('index');
            Route::get('{type}/create', [TutorResourceSheetController::class, 'create'])->whereIn('type', ['mcq', 'short_answer'])->name('create');
            Route::post('{type}', [TutorResourceSheetController::class, 'store'])->whereIn('type', ['mcq', 'short_answer'])->name('store');

            Route::get('sheets/{sheet}', [TutorResourceSheetController::class, 'show'])->name('show');
            Route::get('sheets/{sheet}/mark', [TutorResourceSheetController::class, 'mark'])->name('mark');
            Route::post('sheets/{sheet}/mark', [TutorResourceSheetController::class, 'saveMark'])->name('mark.save');
            Route::delete('sheets/{sheet}', [TutorResourceSheetController::class, 'destroy'])->name('destroy');
        });

        // Games — Spelling Meow. Tutor reviews their students' rounds + feedback.
        Route::prefix('games/spelling')->name('games.spelling.')->group(function () {
            Route::get('/', [TutorSpellingController::class, 'index'])->name('index');
            Route::get('{attempt}', [TutorSpellingController::class, 'show'])->name('show');
            Route::post('{attempt}/feedback', [TutorSpellingController::class, 'feedback'])->name('feedback');
        });

        // Games — Multiplication Rabbit. Tutor reviews their students' rounds + feedback.
        Route::prefix('games/multiplication')->name('games.multiplication.')->group(function () {
            Route::get('/', [TutorMultiplicationController::class, 'index'])->name('index');
            Route::get('{attempt}', [TutorMultiplicationController::class, 'show'])->name('show');
            Route::post('{attempt}/feedback', [TutorMultiplicationController::class, 'feedback'])->name('feedback');
        });
    });

// Student-only area — RoleMiddleware blocks tutors from these routes.
Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('homework', [StudentHomeworkController::class, 'index'])->name('homework.index');
        Route::patch('homework/{homework}/submit', [StudentHomeworkController::class, 'submit'])->name('homework.submit');
        Route::get('homework/{homework}/download', [StudentHomeworkController::class, 'download'])->name('homework.download');
        Route::get('homework/{homework}', [StudentHomeworkController::class, 'show'])->name('homework.show');

        // Messages — student inbox (read-only; cannot reply in MVP).
        Route::get('messages', [StudentMessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [StudentMessageController::class, 'show'])->name('messages.show');

        // Fees — parent unlock gate, then read-only fee page.
        Route::get('fees/unlock', [StudentFeeController::class, 'unlock'])->name('fees.unlock');
        // Throttle the password attempts — the fee password is shared/static, so
        // without this it's brute-forceable on a shared device (SECURITY.md §3).
        Route::post('fees/unlock', [StudentFeeController::class, 'attemptUnlock'])
            ->middleware('throttle:5,1')
            ->name('fees.unlock.attempt');
        Route::post('fees/lock', [StudentFeeController::class, 'lock'])->name('fees.lock');
        Route::get('fees', [StudentFeeController::class, 'index'])->middleware('fee.unlocked')->name('fees.index');

        // Exam Papers — all students can browse and download all papers.
        Route::get('exam-papers', [StudentExamPaperController::class, 'index'])->name('exam-papers.index');
        Route::get('exam-papers/{examPaper}/download', [StudentExamPaperController::class, 'download'])->name('exam-papers.download');

        // Quizzes — take assigned quizzes, view results, write corrections.
        Route::get('quizzes', [StudentQuizController::class, 'index'])->name('quizzes.index');
        Route::get('quizzes/questions/{question}/image', [StudentQuizController::class, 'questionImage'])->name('quizzes.questions.image');
        // The tutor's drawn feedback image on one of this student's answers.
        Route::get('quizzes/answers/{answer}/feedback-image', [StudentQuizController::class, 'feedbackImage'])->name('quizzes.answers.feedback-image');
        Route::get('quizzes/{quiz}', [StudentQuizController::class, 'show'])->name('quizzes.show');
        Route::post('quizzes/{quiz}/submit', [StudentQuizController::class, 'submit'])->name('quizzes.submit');
        Route::get('quizzes/{quiz}/result', [StudentQuizController::class, 'result'])->name('quizzes.result');
        Route::post('quizzes/{quiz}/corrections', [StudentQuizController::class, 'saveCorrections'])->name('quizzes.corrections');

        // Resources — fill a sheet a tutor sent, or build + submit your own
        // (e.g. to record your answers to a paper exam) for the tutor to mark.
        Route::prefix('resources')->name('resources.')->group(function () {
            Route::get('{type}', [StudentResourceSheetController::class, 'index'])->whereIn('type', ['mcq', 'short_answer'])->name('index');
            Route::get('{type}/create', [StudentResourceSheetController::class, 'create'])->whereIn('type', ['mcq', 'short_answer'])->name('create');
            Route::post('{type}', [StudentResourceSheetController::class, 'store'])->whereIn('type', ['mcq', 'short_answer'])->name('store');

            Route::get('sheets/{sheet}', [StudentResourceSheetController::class, 'show'])->name('show');
            Route::post('sheets/{sheet}/submit', [StudentResourceSheetController::class, 'submit'])->name('submit');
        });

        // Games — Spelling Meow. Play a round, see My Progress, write reflections.
        Route::prefix('games/spelling')->name('games.spelling.')->group(function () {
            Route::get('/', [StudentSpellingController::class, 'play'])->name('play');
            Route::post('finish', [StudentSpellingController::class, 'finish'])->name('finish');
            Route::get('progress', [StudentSpellingController::class, 'progress'])->name('progress');
            Route::get('{attempt}', [StudentSpellingController::class, 'show'])->name('show');
            Route::patch('{attempt}/reflection', [StudentSpellingController::class, 'reflection'])->name('reflection');
        });

        // Games — Multiplication Rabbit. Play a round, see My Progress, write reflections.
        Route::prefix('games/multiplication')->name('games.multiplication.')->group(function () {
            Route::get('/', [StudentMultiplicationController::class, 'play'])->name('play');
            Route::post('finish', [StudentMultiplicationController::class, 'finish'])->name('finish');
            Route::get('progress', [StudentMultiplicationController::class, 'progress'])->name('progress');
            Route::get('{attempt}', [StudentMultiplicationController::class, 'show'])->name('show');
            Route::patch('{attempt}/reflection', [StudentMultiplicationController::class, 'reflection'])->name('reflection');
        });
    });

require __DIR__.'/auth.php';
