<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
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
        $quizzes = Quiz::withCount(['questions', 'assignments', 'attempts'])
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
        $quiz->load(['questions', 'attempts.student']);

        $students = User::where('role', 'student')->orderBy('name')->get(['id', 'name']);
        $assignedIds = $quiz->assignments()->pluck('student_id')->all();

        return view('tutor.quizzes.show', compact('quiz', 'students', 'assignedIds'));
    }

    /**
     * Sync the set of students this quiz is assigned to.
     */
    public function assign(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids'   => ['array'],
            'student_ids.*' => [Rule::exists('users', 'id')->where('role', 'student')],
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
        abort_unless($question->hasImage(), 404);

        return Storage::disk('r2')->response($question->image_path, $question->image_name);
    }
}
