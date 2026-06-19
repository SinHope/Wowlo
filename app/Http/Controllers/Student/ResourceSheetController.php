<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AnswerSheet;
use App\Models\Message;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Resources — answer sheets, student side. A student fills in a sheet their
 * tutor sent, OR builds + submits their own (e.g. to record their answers to a
 * paper exam) for the tutor to mark. A student can only ever see/touch their
 * own sheets (student_id, checked server-side); anything else 404s.
 */
class ResourceSheetController extends Controller
{
    private const MAX_QUESTIONS = 100;
    private const MCQ_OPTIONS = 4;

    public function index(string $type): View
    {
        $sheets = AnswerSheet::where('student_id', auth()->id())
            ->where('type', $type)
            ->withCount('questions')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('student.resources.index', [
            'type'      => $type,
            'typeLabel' => config("wowlo.answer_sheet_types.$type"),
            'sheets'    => $sheets,
        ]);
    }

    public function create(string $type): View
    {
        return view('student.resources.create', [
            'type'      => $type,
            'typeLabel' => config("wowlo.answer_sheet_types.$type"),
            'subjects'  => config('wowlo.subjects'),
        ]);
    }

    /** Build + fill + submit a student's own sheet in one go. */
    public function store(Request $request, string $type): RedirectResponse
    {
        // A student always belongs to a tutor — that's who receives the submission.
        $tutorId = auth()->user()->tutor_id;
        abort_if($tutorId === null, 404);

        $rules = [
            'title'     => ['required', 'string', 'max:255'],
            'subject'   => ['required', Rule::in(config('wowlo.subjects'))],
            'questions' => ['required', 'array', 'min:1', 'max:' . self::MAX_QUESTIONS],
        ];
        if ($type === 'mcq') {
            $rules['questions.*.choice'] = ['nullable', 'integer', 'between:1,' . self::MCQ_OPTIONS];
        } else {
            $rules['questions.*.answer_text'] = ['nullable', 'string', 'max:5000'];
        }

        $validated = $request->validate($rules, [
            'questions.required' => 'Add at least one question.',
            'questions.min'      => 'Add at least one question.',
        ]);

        $sheet = DB::transaction(function () use ($validated, $type, $tutorId) {
            $sheet = AnswerSheet::create([
                'author_id'    => auth()->id(),
                'tutor_id'     => $tutorId,
                'student_id'   => auth()->id(),
                'type'         => $type,
                'title'        => $validated['title'],
                'subject'      => $validated['subject'],
                'status'       => 'submitted',
                'submitted_at' => now(),
                // Student sheets are 1 mark per row; the tutor marks correct/wrong.
                'total_marks'  => count($validated['questions']),
            ]);

            foreach (array_values($validated['questions']) as $i => $q) {
                $sheet->questions()->create([
                    'order'       => $i,
                    'num_options' => self::MCQ_OPTIONS,
                    'marks'       => 1,
                    'choice'      => $type === 'mcq' ? ($q['choice'] ?? null) : null,
                    'answer_text' => $type === 'mcq' ? null : ($q['answer_text'] ?? null),
                ]);
            }

            return $sheet;
        });

        $this->notifyTutor($sheet);

        return redirect()->route('student.resources.index', $type)
            ->with('status', 'Submitted to your tutor for marking.');
    }

    public function show(AnswerSheet $sheet): View
    {
        $this->ensureOwned($sheet);
        $sheet->load('questions');

        return view('student.resources.show', compact('sheet'));
    }

    /** Submit answers for a sheet the tutor sent (fillable once). */
    public function submit(Request $request, AnswerSheet $sheet): RedirectResponse
    {
        $this->ensureOwned($sheet);
        abort_unless($sheet->isSent(), 404);

        $sheet->load('questions');

        $rules = [];
        foreach ($sheet->questions as $q) {
            $rules["answers.{$q->id}"] = $sheet->isMcq()
                ? ['nullable', 'integer', 'between:1,' . $q->num_options]
                : ['nullable', 'string', 'max:5000'];
        }
        $request->validate($rules);

        DB::transaction(function () use ($request, $sheet) {
            foreach ($sheet->questions as $q) {
                $answer = $request->input("answers.{$q->id}");
                $q->update($sheet->isMcq()
                    ? ['choice' => $answer !== null && $answer !== '' ? (int) $answer : null]
                    : ['answer_text' => $answer ?: null]);
            }

            $sheet->update(['status' => 'submitted', 'submitted_at' => now()]);
        });

        $this->notifyTutor($sheet);

        return redirect()->route('student.resources.index', $sheet->type)
            ->with('status', 'Submitted to your tutor for marking.');
    }

    /** Guard: the sheet must belong to the acting student (404, not 403). */
    private function ensureOwned(AnswerSheet $sheet): void
    {
        abort_unless($sheet->student_id === auth()->id(), 404);
    }

    /** Tell the owning tutor a sheet was submitted (inbox + best-effort push). */
    private function notifyTutor(AnswerSheet $sheet): void
    {
        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $sheet->tutor_id,
            'subject'     => "Answer sheet submitted: {$sheet->title}",
            'body'        => auth()->user()->name . " submitted a {$sheet->typeLabel()} for you to mark.",
        ]);

        try {
            $message->receiver?->notify(new NewMessageNotification($message));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
