<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Notifications\NewMessageNotification;
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
            ->withCount([
                'questions', 'assignments', 'attempts',
                // Submitted short-answer attempts still awaiting this tutor's marking.
                'attempts as to_mark_count' => fn ($q) => $q
                    ->whereNotNull('completed_at')
                    ->whereNull('graded_at')
                    ->whereHas('quiz.questions', fn ($qq) => $qq->where('question_type', 'short_answer')),
            ])
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
                $isMcq = $q['question_type'] === 'mcq';

                $attrs = [
                    'question_text'  => $q['question_text'],
                    'question_type'  => $q['question_type'],
                    // MCQ carries options + a correct answer; short answers carry none.
                    'option_a'       => $isMcq ? $q['option_a'] : null,
                    'option_b'       => $isMcq ? $q['option_b'] : null,
                    'option_c'       => $isMcq ? $q['option_c'] : null,
                    'option_d'       => $isMcq ? $q['option_d'] : null,
                    'correct_answer' => $isMcq ? $q['correct_answer'] : null,
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

        // Share the already-loaded quiz with each attempt so needsMarking()/
        // isGraded() in the view don't trigger a query per row.
        $quiz->attempts->each(fn (QuizAttempt $a) => $a->setRelation('quiz', $quiz));

        $students = auth()->user()->students()->orderBy('name')->get(['id', 'name']);
        $assignedIds = $quiz->assignments()->pluck('student_id')->all();
        $attemptsByStudent = $quiz->attempts->keyBy('student_id');

        return view('tutor.quizzes.show', compact('quiz', 'students', 'assignedIds', 'attemptsByStudent'));
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
     * Save the tutor's feedback on a completed attempt and notify the student —
     * the feedback lands in their inbox as a Message (plus a best-effort push).
     */
    public function feedback(Request $request, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        $this->ensureOwned($quiz);
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        // Feedback is only for students who have actually submitted.
        abort_unless($attempt->isCompleted(), 404);

        $validated = $request->validate([
            'feedback' => ['required', 'string', 'max:2000'],
        ]);

        $attempt->update(['feedback' => $validated['feedback']]);

        $this->notifyStudentFeedback($quiz, $attempt, $validated['feedback']);

        return back()->with('status', 'Feedback sent — the student has been notified in their inbox.');
    }

    /**
     * The grading page for a submitted attempt — see each answer, mark every
     * short answer (Correct/Partial/Wrong + marks), add per-question feedback,
     * and write overall remarks.
     */
    public function grade(Quiz $quiz, QuizAttempt $attempt): View
    {
        $this->ensureOwned($quiz);
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->isCompleted(), 404);

        $quiz->load('questions');
        $attempt->load('student', 'answers');
        $answersByQuestion = $attempt->answers->keyBy('question_id');

        return view('tutor.quizzes.grade', compact('quiz', 'attempt', 'answersByQuestion'));
    }

    /**
     * Save the tutor's marking. Recomputes obtained marks server-side from the
     * per-question marks awarded, stamps graded_at, stores overall remarks, and
     * notifies the student.
     */
    public function saveGrade(Request $request, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        $this->ensureOwned($quiz);
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->isCompleted(), 404);

        $attempt->load('answers.question');

        // Marks may be awarded in half-steps (schools give 1/2 for partial answers).
        $halfStep = function (string $attribute, $value, $fail) {
            if (fmod((float) $value * 2, 1.0) !== 0.0) {
                $fail('Marks must be a whole number or a half (e.g. 0.5, 1, 1.5).');
            }
        };

        // Build per-answer rules so short answers must get a grade + valid marks,
        // and any answer may carry feedback text + a (jpg/png) feedback image.
        $rules = ['remarks' => ['nullable', 'string', 'max:2000']];
        foreach ($attempt->answers as $answer) {
            $rules["tutor_feedback.{$answer->id}"]  = ['nullable', 'string', 'max:2000'];
            $rules["feedback_image.{$answer->id}"]  = ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:10240'];
            if ($answer->question->isShortAnswer()) {
                $rules["grades.{$answer->id}"] = ['required', Rule::in(['correct', 'partial', 'wrong'])];
                $rules["marks.{$answer->id}"]  = ['required', 'numeric', 'min:0', 'max:' . $answer->question->marks, $halfStep];
            }
        }

        $validated = $request->validate($rules, [
            'grades.*.required' => 'Mark every short answer.',
            'marks.*.required'  => 'Enter the marks awarded.',
            'marks.*.max'       => 'Marks cannot exceed the question total.',
        ]);

        $remarks = $validated['remarks'] ?? null;

        DB::transaction(function () use ($request, $attempt, $remarks) {
            $obtained = 0;

            foreach ($attempt->answers as $answer) {
                $update = [
                    'tutor_feedback' => $request->input("tutor_feedback.{$answer->id}") ?: null,
                ];

                if ($answer->question->isShortAnswer()) {
                    $grade = $request->input("grades.{$answer->id}");
                    // Clamp as a server-side safety net on top of validation.
                    $marks = max(0, min((float) $request->input("marks.{$answer->id}"), $answer->question->marks));

                    $update['grade']         = $grade;
                    $update['marks_awarded'] = $marks;
                    $update['is_correct']    = $grade === 'correct';
                }

                // Optional drawn explanation → private R2 bucket (replaces any old one).
                if ($request->hasFile("feedback_image.{$answer->id}")) {
                    if ($answer->tutor_feedback_image_path) {
                        Storage::disk('r2')->delete($answer->tutor_feedback_image_path);
                    }
                    $file = $request->file("feedback_image.{$answer->id}");
                    $update['tutor_feedback_image_path'] = $file->store('quiz-feedback', 'r2');
                    $update['tutor_feedback_image_name'] = $file->getClientOriginalName();
                }

                $answer->update($update);
                $obtained += $answer->marks_awarded;
            }

            $attempt->update([
                'obtained_marks' => $obtained,
                'graded_at'      => now(),
                'feedback'       => $remarks,
            ]);
        });

        // Notify the student that their quiz is marked (with remarks if given).
        $this->notifyStudentFeedback(
            $quiz,
            $attempt,
            filled($remarks)
                ? $remarks
                : "Your quiz \"{$quiz->title}\" has been marked — tap to see your results and feedback.",
        );

        return redirect()->route('tutor.quizzes.show', $quiz)
            ->with('status', "Marked {$attempt->student->name}'s quiz — they've been notified.");
    }

    /**
     * Stream a per-answer tutor-feedback image from R2 (owning tutor only).
     */
    public function feedbackImage(QuizAnswer $answer): StreamedResponse
    {
        abort_unless($answer->hasTutorFeedbackImage(), 404);
        // Tenancy: the answer's quiz must belong to the acting teacher.
        abort_unless($answer->question->quiz->tutor_id === auth()->id(), 404);

        return Storage::disk('r2')->response($answer->tutor_feedback_image_path, $answer->tutor_feedback_image_name);
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

    /**
     * Drop the feedback into the student's inbox as a Message (+ best-effort
     * push), so it surfaces like a notice. Shared by feedback() and saveGrade().
     */
    private function notifyStudentFeedback(Quiz $quiz, QuizAttempt $attempt, string $body): void
    {
        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $attempt->student_id,
            'subject'     => "Feedback on your quiz: {$quiz->title}",
            'body'        => $body,
        ]);

        try {
            $message->receiver?->notify(new NewMessageNotification($message));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
