<?php

namespace App\Http\Controllers\Tutor;

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
 * Resources — answer sheets, tutor side. A tutor (or super_admin) builds a
 * blank MCQ/OAS or short-answer sheet, sends it to ONE of their students, then
 * marks it manually after the student submits. Everything is scoped to the
 * acting tutor (tutor_id, set server-side); cross-tenant access 404s.
 */
class ResourceSheetController extends Controller
{
    /** Max rows on one sheet — a guard against absurd/abusive payloads. */
    private const MAX_QUESTIONS = 100;

    /** Fixed number of options on an MCQ/OAS row (1–4), per the spec. */
    private const MCQ_OPTIONS = 4;

    public function index(string $type): View
    {
        $sheets = AnswerSheet::where('tutor_id', auth()->id())
            ->where('type', $type)
            ->with('student:id,name')
            ->withCount('questions')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('tutor.resources.index', [
            'type'      => $type,
            'typeLabel' => config("wowlo.answer_sheet_types.$type"),
            'sheets'    => $sheets,
        ]);
    }

    public function create(string $type): View
    {
        return view('tutor.resources.create', [
            'type'      => $type,
            'typeLabel' => config("wowlo.answer_sheet_types.$type"),
            'subjects'  => config('wowlo.subjects'),
            'students'  => auth()->user()->students()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'subject'             => ['required', Rule::in(config('wowlo.subjects'))],
            // Tenancy: may only send to one of THIS tutor's own students.
            'student_id'          => ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'student')->where('tutor_id', auth()->id()))],
            // Optional tutor instructions (whole sheet + per question).
            'remarks'             => ['nullable', 'string', 'max:2000'],
            'questions'           => ['required', 'array', 'min:1', 'max:' . self::MAX_QUESTIONS],
            'questions.*.marks'   => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'student_id.required' => 'Choose a student to send this to.',
            'questions.required'  => 'Add at least one question.',
            'questions.min'       => 'Add at least one question.',
        ]);

        $sheet = DB::transaction(function () use ($validated, $type) {
            $sheet = AnswerSheet::create([
                'author_id'   => auth()->id(),
                'tutor_id'    => auth()->id(),
                'student_id'  => $validated['student_id'],
                'type'        => $type,
                'title'       => $validated['title'],
                'subject'     => $validated['subject'],
                'remarks'     => $validated['remarks'] ?? null,
                'status'      => 'sent',
                // Server-computed snapshot — never trust a client total.
                'total_marks' => collect($validated['questions'])->sum('marks'),
            ]);

            foreach (array_values($validated['questions']) as $i => $q) {
                $sheet->questions()->create([
                    'order'       => $i,
                    'num_options' => self::MCQ_OPTIONS,
                    'marks'       => $q['marks'],
                    'remarks'     => $q['remarks'] ?? null,
                ]);
            }

            return $sheet;
        });

        $this->notify(
            $sheet->student_id,
            "New answer sheet to complete: {$sheet->title}",
            "Your tutor sent you a {$sheet->typeLabel()} to fill in. Open Resources to complete and submit it.",
        );

        return redirect()->route('tutor.resources.index', $type)
            ->with('status', "Sheet sent to {$sheet->student->name}.");
    }

    public function show(AnswerSheet $sheet): View
    {
        $this->ensureOwned($sheet);
        $sheet->load('questions', 'student:id,name');

        return view('tutor.resources.show', compact('sheet'));
    }

    public function mark(AnswerSheet $sheet): View
    {
        $this->ensureOwned($sheet);
        // Only after the student has submitted (re-marking a marked sheet is fine).
        abort_unless($sheet->isSubmitted() || $sheet->isMarked(), 404);

        $sheet->load('questions', 'student:id,name');

        return view('tutor.resources.mark', compact('sheet'));
    }

    public function saveMark(Request $request, AnswerSheet $sheet): RedirectResponse
    {
        $this->ensureOwned($sheet);
        abort_unless($sheet->isSubmitted() || $sheet->isMarked(), 404);

        $sheet->load('questions');

        // Marks may be awarded in half-steps (schools give 1/2 for partial answers).
        $halfStep = function (string $attribute, $value, $fail) {
            if (fmod((float) $value * 2, 1.0) !== 0.0) {
                $fail('Marks must be a whole number or a half (e.g. 0.5, 1, 1.5).');
            }
        };

        $rules = ['feedback' => ['nullable', 'string', 'max:2000']];
        foreach ($sheet->questions as $q) {
            $rules["grades.{$q->id}"]    = ['required', Rule::in(['correct', 'partial', 'wrong'])];
            // The tutor decides how many marks each question is worth.
            $rules["qmarks.{$q->id}"]    = ['required', 'integer', 'min:1', 'max:100'];
            // Awarded marks can't exceed the (tutor-chosen) question total.
            $rules["marks.{$q->id}"]     = ['required', 'numeric', 'min:0', 'lte:qmarks.' . $q->id, $halfStep];
            $rules["qfeedback.{$q->id}"] = ['nullable', 'string', 'max:2000'];
        }

        $request->validate($rules, [
            'grades.*.required' => 'Mark every question.',
            'qmarks.*.required' => 'Set how many marks this question is worth.',
            'marks.*.required'  => 'Enter the marks awarded.',
            'marks.*.lte'       => 'Awarded marks cannot exceed the question total.',
        ]);

        DB::transaction(function () use ($request, $sheet) {
            $total = 0;
            $obtained = 0;

            foreach ($sheet->questions as $q) {
                $qmarks = (int) $request->input("qmarks.{$q->id}");
                $grade  = $request->input("grades.{$q->id}");
                // Clamp as a server-side safety net on top of validation.
                $marks  = max(0, min((float) $request->input("marks.{$q->id}"), $qmarks));

                $q->update([
                    'marks'          => $qmarks,
                    'grade'          => $grade,
                    'marks_awarded'  => $marks,
                    'tutor_feedback' => $request->input("qfeedback.{$q->id}") ?: null,
                ]);

                $total    += $qmarks;
                $obtained += $marks;
            }

            $sheet->update([
                'total_marks'    => $total,      // recomputed from the per-question marks
                'obtained_marks' => $obtained,
                'status'         => 'marked',
                'marked_at'      => now(),
                'feedback'       => $request->input('feedback') ?: null,
            ]);
        });

        $this->notify(
            $sheet->student_id,
            "Your answer sheet is marked: {$sheet->title}",
            filled($sheet->feedback)
                ? $sheet->feedback
                : "Your {$sheet->typeLabel()} has been marked — open Resources to see your results.",
        );

        return redirect()->route('tutor.resources.show', $sheet)
            ->with('status', "Marked {$sheet->student->name}'s sheet — they've been notified.");
    }

    public function destroy(AnswerSheet $sheet): RedirectResponse
    {
        $this->ensureOwned($sheet);
        $type = $sheet->type;

        $sheet->delete(); // cascades to questions

        return redirect()->route('tutor.resources.index', $type)
            ->with('status', 'Sheet deleted.');
    }

    /** Guard: the sheet must belong to the acting tutor (404, not 403). */
    private function ensureOwned(AnswerSheet $sheet): void
    {
        abort_unless($sheet->tutor_id === auth()->id(), 404);
    }

    /** Drop a notice into the recipient's inbox (+ best-effort web push). */
    private function notify(int $receiverId, string $subject, string $body): void
    {
        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $receiverId,
            'subject'     => $subject,
            'body'        => $body,
        ]);

        try {
            $message->receiver?->notify(new NewMessageNotification($message));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
