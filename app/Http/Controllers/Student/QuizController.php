<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
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

        $validated = $request->validate([
            'answers'   => ['required', 'array'],
            'answers.*' => ['nullable', 'in:A,B,C,D'],
        ]);
        $answers = $validated['answers'];

        DB::transaction(function () use ($quiz, $request, $answers) {
            $attempt = QuizAttempt::updateOrCreate(
                ['quiz_id' => $quiz->id, 'student_id' => $request->user()->id],
                ['total_marks' => $quiz->totalMarks(), 'obtained_marks' => 0, 'completed_at' => now()],
            );

            // Fresh answers in case of a resumed attempt.
            $attempt->answers()->delete();

            $obtained = 0;
            foreach ($quiz->questions as $question) {
                $chosen = $answers[$question->id] ?? null;
                $isCorrect = $chosen !== null && $chosen === $question->correct_answer;
                $awarded = $isCorrect ? $question->marks : 0;
                $obtained += $awarded;

                $attempt->answers()->create([
                    'question_id'   => $question->id,
                    'student_answer' => $chosen,
                    'is_correct'    => $isCorrect,
                    'marks_awarded' => $awarded,
                ]);
            }

            $attempt->update(['obtained_marks' => $obtained]);
        });

        return redirect()->route('student.quizzes.result', $quiz)
            ->with('status', 'Quiz submitted! Here are your results.');
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
