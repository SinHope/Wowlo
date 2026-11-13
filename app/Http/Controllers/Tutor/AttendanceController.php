<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Attendance Tracking (docs/attendance-tracking.md).
 *
 * Tenancy: every action resolves the student through
 * `$request->user()->students()` so another tutor's student (or a non-student
 * id) is a 404, never a 403 — ids don't leak. tutor_id is set here, server-side.
 */
class AttendanceController extends Controller
{
    /**
     * First view: pick one of YOUR students.
     */
    public function index(Request $request): View
    {
        $students = $request->user()->students()->orderBy('name')->get(['id', 'name']);

        return view('tutor.attendance.index', compact('students'));
    }

    /**
     * Calendar / list page for one student.
     */
    public function show(Request $request, string $student): View
    {
        $student = $this->ownedStudent($request, $student);

        return view('tutor.attendance.show', [
            'student' => $student,
            'config' => [
                'studentId' => $student->id,
                'rows' => $this->rows($student),
                'today' => now()->toDateString(),                  // server (Singapore) date — never the browser clock
                'maxDate' => now()->addYear()->toDateString(),
                'readonly' => false,
                'justSaved' => session()->has('status'),
                ...$this->viewState($request),
            ],
        ]);
    }

    /**
     * Save: persist the ticks that changed.
     */
    public function update(AttendanceRequest $request, string $student): RedirectResponse
    {
        $student = $this->ownedStudent($request, $student);
        $data = $request->validated();

        $locked = $this->persist($request->user(), $student, $data['added'] ?? [], $data['removed'] ?? []);

        $redirect = redirect()
            ->route('tutor.attendance.show', ['student' => $student->id, ...$this->returnState($data)])
            ->with('status', 'Attendance saved.');

        if ($locked > 0) {
            $redirect->with('warning', $locked.' lesson(s) are already billed and were not changed. Edit the bill to change them.');
        }

        return $redirect;
    }

    /**
     * Add to Billing: save, then open the WhatsApp bill form pre-filled with the
     * unbilled attended dates in the window. Which dates go in is decided HERE
     * from the database — never from client-sent dates.
     */
    public function toBilling(AttendanceRequest $request, string $student): RedirectResponse
    {
        $student = $this->ownedStudent($request, $student);
        $data = $request->validated();

        $added = $data['added'] ?? [];
        $removed = $data['removed'] ?? [];
        $from = $data['window_from'];
        $to = $data['window_to'];

        // Simulate the post-save state BEFORE saving, so a blocked attempt saves nothing.
        $rows = $student->attendances()->get(['lesson_date', 'bill_id'])
            ->map(fn (Attendance $a) => ['date' => $a->lesson_date->format('Y-m-d'), 'billed' => $a->bill_id !== null]);
        $billedDates = $rows->where('billed', true)->pluck('date')->all();
        $final = $rows->where('billed', false)->pluck('date')
            ->merge(array_diff($added, $billedDates))
            ->diff($removed)
            ->unique()
            ->filter(fn (string $d) => $d >= $from && $d <= $to)
            ->sort()
            ->values();

        $future = $final->filter(fn (string $d) => $d > now()->toDateString())->values();
        if ($future->isNotEmpty()) {
            return back()->withErrors([
                'billing' => "Please recheck the dates. A later date can't be added to billing: "
                    .$future->map(fn (string $d) => date('j M Y', strtotime($d)))->implode(', ').'.',
            ]);
        }

        if ($final->isEmpty()) {
            return back()->withErrors(['billing' => 'Tick at least one unbilled lesson first.']);
        }

        $this->persist($request->user(), $student, $added, $removed);

        // Authoritative: re-read what is actually saved and unbilled in the window.
        $dates = $student->attendances()
            ->whereNull('bill_id')
            ->whereBetween('lesson_date', [$from, $to])
            ->orderBy('lesson_date')
            ->get(['lesson_date'])
            ->map(fn (Attendance $a) => $a->lesson_date->format('Y-m-d'))
            ->values();

        $billingMonth = ($data['view'] ?? null) === 'calendar' && ! empty($data['month'])
            ? $data['month']
            : substr($dates->last(), 0, 7);

        session()->flash('attendance_prefill', [
            'student_id' => $student->id,
            'dates' => $dates->all(),
            'billing_month' => $billingMonth,
        ]);

        return redirect()->route('tutor.billing.create');
    }

    /**
     * Resolve a student that belongs to the acting tutor, else 404.
     */
    private function ownedStudent(Request $request, string $id): User
    {
        abort_unless(ctype_digit($id), 404);

        return $request->user()->students()->findOrFail($id);
    }

    /**
     * Every saved day with its derived state: attended (editable) → billed
     * (locked) → paid (locked; derived from the ledger, never stored).
     *
     * @return array<int, array{date: string, status: string}>
     */
    private function rows(User $student): array
    {
        $paid = Ledger::paidBillIds($student);

        return $student->attendances()
            ->orderBy('lesson_date')
            ->get(['lesson_date', 'bill_id'])
            ->map(fn (Attendance $a) => [
                'date' => $a->lesson_date->format('Y-m-d'),
                'status' => $a->bill_id === null
                    ? 'attended'
                    : (in_array($a->bill_id, $paid) ? 'paid' : 'billed'),
            ])
            ->values()
            ->all();
    }

    /**
     * Add the new ticks (idempotent) and remove the unticked ones — but never
     * touch a day a bill already charges for. Returns how many requested
     * removals were skipped because they were billed.
     */
    private function persist(User $tutor, User $student, array $added, array $removed): int
    {
        $added = array_values(array_diff($added, $removed));
        $lockedSkipped = 0;

        DB::transaction(function () use ($tutor, $student, $added, $removed, &$lockedSkipped) {
            if ($added) {
                $now = now();
                Attendance::insertOrIgnore(array_map(fn (string $d) => [
                    'tutor_id' => $tutor->id,      // server-side only
                    'student_id' => $student->id,
                    'lesson_date' => $d,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $added));
            }

            if ($removed) {
                $lockedSkipped = Attendance::where('student_id', $student->id)
                    ->whereNotNull('bill_id')
                    ->whereIn('lesson_date', $removed)
                    ->count();

                Attendance::where('student_id', $student->id)
                    ->whereNull('bill_id')
                    ->whereIn('lesson_date', $removed)
                    ->delete();
            }
        });

        return $lockedSkipped;
    }

    /**
     * Which view / month / list-filter to open, taken leniently from the query
     * string (anything invalid silently falls back to the defaults).
     *
     * @return array{view: string, month: string, from: ?string, to: ?string}
     */
    private function viewState(Request $request): array
    {
        $validator = Validator::make($request->query(), [
            'view' => ['nullable', Rule::in(['calendar', 'list'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $get = fn (string $key) => $validator->errors()->has($key) ? null : $request->query($key);

        $from = $get('from');
        $to = $get('to');
        if (! $from || ! $to || $from > $to) {
            $from = $to = null;
        }

        return [
            'view' => $get('view') ?? 'calendar',
            'month' => $get('month') ?? now()->format('Y-m'),
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * The view state to carry through a redirect after saving.
     */
    private function returnState(array $data): array
    {
        return array_filter([
            'view' => $data['view'] ?? null,
            'month' => $data['month'] ?? null,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
        ]);
    }
}
