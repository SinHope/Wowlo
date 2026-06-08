<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizController extends Controller
{
    public function index(): View
    {
        $quizzes = Quiz::where('tutor_id', auth()->id())
            ->withCount(['questions', 'assignments', 'attempts'])
            ->latest('id')
            ->paginate(15);

        return view('tutor.quizzes.index', compact('quizzes'));
    }

    public function create(): View
    {
        return view('tutor.quizzes.create', [
            'levels'    => config('wowlo.levels'),
            'subjects'  => config('wowlo.subjects'),
            'examTypes' => config('wowlo.exam_types'),
        ]);
    }

    public function store(QuizRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $quiz = DB::transaction(function () use ($data, $request) {
            $quiz = Quiz::create([
                'tutor_id'  => $request->user()->id,
                'title'     => $data['title'],
                'level'     => $data['level'],
                'subject'   => $data['subject'],
                'topic'     => $data['topic'] ?? null,
                'exam_type' => $data['exam_type'],
            ]);

            $order = 0;
            foreach ($data['questions'] as $q) {
                $attrs = [
                    'question_text'  => $q['question_text'],
                    'question_type'  => 'mcq',
                    'option_a'       => $q['option_a'],
                    'option_b'       => $q['option_b'],
                    'option_c'       => $q['option_c'],
                    'option_d'       => $q['option_d'],
                    'correct_answer' => $q['correct_answer'],
                    'marks'          => $q['marks'],
                    'order'          => $order++,
                ];

                // Optional diagram/attachment → private R2 bucket.
                if (! empty($q['image'])) {
                    $attrs['image_path'] = $q['image']->store('quiz-questions', 'r2');
                    $attrs['image_name'] = $q['image']->getClientOriginalName();
                }

                $quiz->questions()->create($attrs);
            }

            return $quiz;
        });

        return redirect()->route('tutor.quizzes.show', $quiz)
            ->with('status', 'Quiz created. Now assign it to students.');
    }

    public function show(Quiz $quiz): View
    {
        $this->ensureOwned($quiz);

        $quiz->load(['questions', 'attempts.student']);

        $students = auth()->user()->students()->orderBy('name')->get(['id', 'name']);
        $assignedIds = $quiz->assignments()->pluck('student_id')->all();

        return view('tutor.quizzes.show', compact('quiz', 'students', 'assignedIds'));
    }

    /**
     * View one student's submitted attempt — the answers they chose, what was
     * correct, the marks, and any corrections they wrote.
     */
    public function attempt(Quiz $quiz, QuizAttempt $attempt): View
    {
        $this->ensureOwned($quiz);
        // The attempt must belong to this quiz (so it's this tutor's data, and
        // an ID from another quiz can't be smuggled in via the URL).
        abort_unless($attempt->quiz_id === $quiz->id, 404);

        $quiz->load('questions');
        $attempt->load('student', 'answers');
        $answersByQuestion = $attempt->answers->keyBy('question_id');

        return view('tutor.quizzes.attempt', compact('quiz', 'attempt', 'answersByQuestion'));
    }

    /**
     * Sync the set of students this quiz is assigned to.
     */
    public function assign(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensureOwned($quiz);

        $validated = $request->validate([
            'student_ids'   => ['array'],
            // Tenancy: may only assign to this teacher's own students.
            'student_ids.*' => [Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'student')->where('tutor_id', auth()->id()))],
        ]);

        $ids = $validated['student_ids'] ?? [];

        DB::transaction(function () use ($quiz, $ids) {
            // Remove assignments that were unchecked.
            $quiz->assignments()->whereNotIn('student_id', $ids ?: [0])->delete();

            // Add newly checked ones (firstOrCreate respects the unique constraint).
            foreach ($ids as $studentId) {
                $quiz->assignments()->firstOrCreate(
                    ['student_id' => $studentId],
                    ['assigned_at' => now()],
                );
            }
        });

        return back()->with('status', 'Assignments updated.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->ensureOwned($quiz);

        // Remove any question attachments from R2 before the DB rows cascade away.
        $keys = $quiz->questions()->whereNotNull('image_path')->pluck('image_path')->all();
        if ($keys) {
            Storage::disk('r2')->delete($keys);
        }

        $quiz->delete(); // cascades to questions, assignments, attempts, answers

        return redirect()->route('tutor.quizzes.index')
            ->with('status', 'Quiz deleted.');
    }

    /**
     * Stream a question's attachment from R2 (tutor can view any).
     */
    public function questionImage(QuizQuestion $question): StreamedResponse
    {
        // Tenancy: the question's quiz must belong to the acting teacher.
        abort_unless($question->quiz->tutor_id === auth()->id(), 404);
        abort_unless($question->hasImage(), 404);

        return Storage::disk('r2')->response($question->image_path, $question->image_name);
    }

    /**
     * Guard: the quiz must belong to the acting teacher.
     */
    private function ensureOwned(Quiz $quiz): void
    {
        abort_unless($quiz->tutor_id === auth()->id(), 404);
    }
}
