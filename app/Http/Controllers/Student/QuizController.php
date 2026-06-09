<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizController extends Controller
{
    /**
     * Quizzes assigned to this student, each with a derived status.
     */
    public function index(Request $request): View
    {
        $student = $request->user();

        $quizzes = Quiz::whereHas('assignments', fn ($q) => $q->where('student_id', $student->id))
            ->withCount('questions')
            ->with('questions') // needed for hasShortAnswers() in the status logic
            ->latest('id')
            ->get();

        // Map quiz_id → this student's attempt (if any) for status derivation.
        $attempts = $student->quizAttempts()->get()->keyBy('quiz_id');

        return view('student.quizzes.index', compact('quizzes', 'attempts'));
    }

    /**
     * The take-quiz page. Redirects to the result once completed.
     */
    public function show(Request $request, Quiz $quiz): View|RedirectResponse
    {
        $this->ensureAssigned($request, $quiz);

        $attempt = $this->attemptFor($request, $quiz);
        if ($attempt && $attempt->isCompleted()) {
            return redirect()->route('student.quizzes.result', $quiz);
        }

        $quiz->load('questions');

        return view('student.quizzes.take', compact('quiz'));
    }

    /**
     * Auto-mark the submission and store the attempt + answers.
     */
    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensureAssigned($request, $quiz);

        // Already done? Don't allow a re-submit.
        $existing = $this->attemptFor($request, $quiz);
        if ($existing && $existing->isCompleted()) {
            return redirect()->route('student.quizzes.result', $quiz);
        }

        $quiz->load('questions');

        // Answers are free text now (short answers); MCQ values are still just a
        // letter, which we re-validate per question in the marking loop below.
        $validated = $request->validate([
            'answers'   => ['array'],
            'answers.*' => ['nullable', 'string', 'max:5000'],
        ]);
        $answers = $validated['answers'] ?? [];

        // A quiz with any short answer can't be fully auto-marked — it waits
        // for the tutor. MCQ-only quizzes are graded the moment they're sent.
        $hasShort = $quiz->hasShortAnswers();

        DB::transaction(function () use ($quiz, $request, $answers, $hasShort) {
            $attempt = QuizAttempt::updateOrCreate(
                ['quiz_id' => $quiz->id, 'student_id' => $request->user()->id],
                [
                    'total_marks'    => $quiz->totalMarks(),
                    'obtained_marks' => 0,
                    'completed_at'   => now(),
                    'graded_at'      => $hasShort ? null : now(),
                ],
            );

            // Fresh answers in case of a resumed attempt.
            $attempt->answers()->delete();

            $obtained = 0;
            foreach ($quiz->questions as $question) {
                $raw = $answers[$question->id] ?? null;

                if ($question->isShortAnswer()) {
                    // Stored pending — no marks until the tutor grades it.
                    $attempt->answers()->create([
                        'question_id'    => $question->id,
                        'student_answer' => filled($raw) ? trim($raw) : null,
                        'is_correct'     => false,
                        'marks_awarded'  => 0,
                        'grade'          => null,
                    ]);

                    continue;
                }

                // MCQ — auto-mark. Only a valid A–D letter counts.
                $chosen = in_array($raw, ['A', 'B', 'C', 'D'], true) ? $raw : null;
                $isCorrect = $chosen !== null && $chosen === $question->correct_answer;
                $awarded = $isCorrect ? $question->marks : 0;
                $obtained += $awarded;

                $attempt->answers()->create([
                    'question_id'    => $question->id,
                    'student_answer' => $chosen,
                    'is_correct'     => $isCorrect,
                    'marks_awarded'  => $awarded,
                    'grade'          => $isCorrect ? 'correct' : 'wrong',
                ]);
            }

            $attempt->update(['obtained_marks' => $obtained]);
        });

        return redirect()->route('student.quizzes.result', $quiz)->with(
            'status',
            $hasShort
                ? 'Quiz submitted! Your tutor will mark your short answers soon.'
                : 'Quiz submitted! Here are your results.',
        );
    }

    /**
     * Results page with per-question correctness and the corrections area.
     */
    public function result(Request $request, Quiz $quiz): View|RedirectResponse
    {
        $this->ensureAssigned($request, $quiz);

        $attempt = $this->attemptFor($request, $quiz);
        if (! $attempt || ! $attempt->isCompleted()) {
            return redirect()->route('student.quizzes.show', $quiz);
        }

        $quiz->load('questions');
        $attempt->load('answers');
        $answersByQuestion = $attempt->answers->keyBy('question_id');

        return view('student.quizzes.result', compact('quiz', 'attempt', 'answersByQuestion'));
    }

    /**
     * Save the student's written corrections for wrong answers.
     */
    public function saveCorrections(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensureAssigned($request, $quiz);

        $attempt = $this->attemptFor($request, $quiz);
        abort_unless($attempt && $attempt->isCompleted(), 404);

        $validated = $request->validate([
            'corrections'   => ['array'],
            'corrections.*' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach (($validated['corrections'] ?? []) as $answerId => $text) {
            $attempt->answers()->where('id', $answerId)->update(['correction' => $text]);
        }

        return back()->with('status', 'Corrections saved.');
    }

    /**
     * Stream a question's diagram — only for a quiz assigned to this student.
     */
    public function questionImage(Request $request, QuizQuestion $question): StreamedResponse
    {
        abort_unless($question->hasImage(), 404);

        $assigned = $question->quiz->assignments()
            ->where('student_id', $request->user()->id)
            ->exists();
        abort_unless($assigned, 403);

        return Storage::disk('r2')->response($question->image_path, $question->image_name);
    }

    /**
     * Stream the tutor's drawn feedback image — only on this student's own answer.
     */
    public function feedbackImage(Request $request, QuizAnswer $answer): StreamedResponse
    {
        abort_unless($answer->hasTutorFeedbackImage(), 404);
        // Isolation: the answer must belong to this student's own attempt.
        abort_unless($answer->attempt->student_id === $request->user()->id, 404);

        return Storage::disk('r2')->response($answer->tutor_feedback_image_path, $answer->tutor_feedback_image_name);
    }

    /**
     * A student may only touch a quiz that is assigned to them.
     */
    private function ensureAssigned(Request $request, Quiz $quiz): void
    {
        $assigned = $quiz->assignments()
            ->where('student_id', $request->user()->id)
            ->exists();
        abort_unless($assigned, 403);
    }

    private function attemptFor(Request $request, Quiz $quiz): ?QuizAttempt
    {
        return QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $request->user()->id)
            ->first();
    }
}
