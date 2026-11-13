<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\BillRequest;
use App\Models\Attendance;
use App\Models\Bill;
use App\Models\BillLine;
use App\Models\User;
use App\Services\BillMessage;
use App\Services\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillController extends Controller
{
    /**
     * History of bills generated, newest first.
     */
    public function index(): View
    {
        $bills = Bill::where('tutor_id', auth()->id())
            ->with('student')
            ->latest('billing_month')
            ->latest('id')
            ->paginate(15);

        return view('tutor.billing.index', compact('bills'));
    }

    /**
     * The WhatsApp billing generator. Each student carries their rate and
     * current outstanding so the Alpine form can compute totals live.
     */
    public function create(): View
    {
        $students = auth()->user()->students()
            ->with('tuitionFee')
            ->orderBy('name')
            ->get()
            ->map(fn (User $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'rate' => $s->tuitionFee?->fee_rate_per_hour !== null
                    ? (float) $s->tuitionFee->fee_rate_per_hour
                    : null,
                'outstanding' => Ledger::outstanding($s),
            ]);

        return view('tutor.billing.create', [
            'prefill' => $this->attendancePrefill(),
            'students' => $students,
            'currency' => config('wowlo.currency'),
            // The acting tutor's own payment line, mirrored into the live preview.
            'paymentInstructions' => auth()->user()->payment_instructions,
        ]);
    }

    /**
     * Recompute everything server-side (never trust client math) and persist
     * the bill, its lesson lines, and its extra charges.
     */
    public function store(BillRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Tenancy: can only bill one of this teacher's own students.
        $student = $request->user()->students()->findOrFail($data['student_id']);

        $fee = $student->tuitionFee;
        if (! $fee) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'Set an hourly rate for this student before billing.']);
        }

        $rate = (float) $fee->fee_rate_per_hour;

        $outstandingBefore = Ledger::outstanding($student);   // snapshot before this bill
        [$lessons, $charges, $totals] = $this->compute($data, $rate, $outstandingBefore);

        $billingMonth = Carbon::createFromFormat('Y-m', $data['billing_month'])->startOfMonth();

        $bill = DB::transaction(function () use ($request, $student, $billingMonth, $lessons, $charges, $totals, $outstandingBefore) {
            $bill = Bill::create([
                'student_id' => $student->id,
                'tutor_id' => $request->user()->id,
                'billing_month' => $billingMonth,
                ...$totals,
                'outstanding_before' => $outstandingBefore,
                'currency' => config('wowlo.currency'),
            ]);

            $bill->lines()->createMany($lessons->all());

            if ($charges->isNotEmpty()) {
                $bill->charges()->createMany($charges->all());
            }

            $this->syncAttendance($bill, $lessons->pluck('lesson_date'));

            return $bill;
        });

        return redirect()
            ->route('tutor.billing.show', $bill)
            ->with('status', 'Bill saved. Copy the message below to send on WhatsApp.');
    }

    /**
     * A saved bill with its generated WhatsApp message.
     */
    public function show(Bill $bill): View
    {
        // Tenancy: only the teacher who issued this bill may view it.
        abort_unless($bill->tutor_id === auth()->id(), 404);

        $bill->load(['student', 'lines', 'charges']);

        return view('tutor.billing.show', [
            'bill' => $bill,
            'message' => BillMessage::for($bill),
        ]);
    }

    /**
     * Edit form. The student is locked; "outstanding" shown is the bill's own
     * saved snapshot, not the live balance (which already includes this bill).
     */
    public function edit(Bill $bill): View
    {
        abort_unless($bill->tutor_id === auth()->id(), 404);

        $bill->load(['student.tuitionFee', 'lines', 'charges']);
        $student = $bill->student;

        return view('tutor.billing.edit', [
            'bill' => $bill,
            'students' => [[
                'id' => $student->id,
                'name' => $student->name,
                'rate' => $student->tuitionFee?->fee_rate_per_hour !== null
                    ? (float) $student->tuitionFee->fee_rate_per_hour
                    : null,
                'outstanding' => (float) $bill->outstanding_before,
            ]],
            'currency' => $bill->currency,
            'paymentInstructions' => auth()->user()->payment_instructions,
            'initial' => [
                'studentId' => $student->id,
                'billingMonth' => $bill->billing_month->format('Y-m'),
                'lessons' => $bill->lines->map(fn ($l) => [
                    'lesson_date' => $l->lesson_date instanceof \DateTimeInterface
                        ? $l->lesson_date->format('Y-m-d')
                        : (string) $l->lesson_date,
                    'hours' => (float) $l->hours,
                ])->values(),
                'charges' => $bill->charges->map(fn ($c) => [
                    'description' => $c->description,
                    'amount' => (float) $c->amount,
                ])->values(),
            ],
        ]);
    }

    /**
     * Re-save an edited bill. Student and the outstanding snapshot are kept;
     * lessons, charges and totals are recomputed server-side.
     */
    public function update(BillRequest $request, Bill $bill): RedirectResponse
    {
        abort_unless($bill->tutor_id === $request->user()->id, 404);

        $data = $request->validated();
        $fee = $bill->student->tuitionFee;
        if (! $fee) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'Set an hourly rate for this student before billing.']);
        }

        [$lessons, $charges, $totals] = $this->compute($data, (float) $fee->fee_rate_per_hour, (float) $bill->outstanding_before);
        $billingMonth = Carbon::createFromFormat('Y-m', $data['billing_month'])->startOfMonth();

        DB::transaction(function () use ($bill, $billingMonth, $lessons, $charges, $totals) {
            $bill->update(['billing_month' => $billingMonth, ...$totals]);

            $bill->lines()->delete();
            $bill->charges()->delete();
            $bill->lines()->createMany($lessons->all());
            if ($charges->isNotEmpty()) {
                $bill->charges()->createMany($charges->all());
            }

            $this->syncAttendance($bill, $lessons->pluck('lesson_date'));
        });

        return redirect()
            ->route('tutor.billing.show', $bill)
            ->with('status', 'Bill updated. Copy the new message below to send on WhatsApp.');
    }

    /**
     * Delete a bill (lines + charges cascade). Outstanding is derived, so the
     * student's balance corrects itself.
     */
    public function destroy(Bill $bill): RedirectResponse
    {
        abort_unless($bill->tutor_id === auth()->id(), 404);

        // Its attendance days go back to "attended" (the FK also nulls this; explicit so it never depends on it).
        Attendance::where('bill_id', $bill->id)->update(['bill_id' => null]);

        $bill->delete();

        return redirect()
            ->route('tutor.billing.index')
            ->with('status', 'Bill deleted.');
    }

    /**
     * Link this bill to the student's matching, not-yet-billed attendance days
     * (so they show as "Billed" and can't be billed twice). Works for bills made
     * from Attendance AND manual ones. On edit it first releases the bill's old
     * days, then re-matches; days already on ANOTHER bill are never stolen.
     */
    private function syncAttendance(Bill $bill, \Illuminate\Support\Collection $lessonDates): void
    {
        Attendance::where('bill_id', $bill->id)->update(['bill_id' => null]);

        $dates = $lessonDates
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->all();

        Attendance::where('student_id', $bill->student_id)
            ->whereNull('bill_id')
            ->whereIn('lesson_date', $dates)
            ->update(['bill_id' => $bill->id]);
    }

    /**
     * The "Add to Billing" hand-off from Attendance (flashed by the attendance
     * controller). Re-checks ownership here, so a forged/stale session value for
     * someone else's student is ignored. Hours default to the student's most
     * recent billed lesson, blank if there is none.
     *
     * @return array{studentId: int, billingMonth: string, lessons: array<int, array{lesson_date: string, hours: float|string}>}|null
     */
    private function attendancePrefill(): ?array
    {
        $flash = session('attendance_prefill');
        if (! is_array($flash) || empty($flash['dates']) || empty($flash['student_id'])) {
            return null;
        }

        $student = auth()->user()->students()->find($flash['student_id']);
        if (! $student) {
            return null;
        }

        $lastHours = BillLine::whereHas('bill', fn ($q) => $q->where('student_id', $student->id))
            ->orderByDesc('lesson_date')
            ->orderByDesc('id')
            ->value('hours');

        return [
            'studentId' => $student->id,
            'billingMonth' => (string) ($flash['billing_month'] ?? now()->format('Y-m')),
            'lessons' => collect($flash['dates'])
                ->map(fn ($d) => [
                    'lesson_date' => Carbon::parse($d)->toDateString(),
                    'hours' => $lastHours !== null ? (float) $lastHours : '',
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Authoritative recompute (never trust client math).
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: array<string, float>}
     */
    private function compute(array $data, float $rate, float $outstandingBefore): array
    {
        $lessons = collect($data['lessons'])->map(fn ($l) => [
            'lesson_date' => $l['lesson_date'],
            'hours' => round((float) $l['hours'], 2),
            'rate' => $rate,
            'amount' => round($rate * (float) $l['hours'], 2),
        ]);

        $charges = collect($data['charges'] ?? [])->map(fn ($c) => [
            'description' => $c['description'],
            'amount' => round((float) $c['amount'], 2),
        ]);

        $lessonsSubtotal = round($lessons->sum('amount'), 2);
        $additionalTotal = round($charges->sum('amount'), 2);
        $chargesTotal = round($lessonsSubtotal + $additionalTotal, 2);

        return [$lessons, $charges, [
            'lessons_subtotal' => $lessonsSubtotal,
            'additional_total' => $additionalTotal,
            'charges_total' => $chargesTotal,
            'grand_total' => round($chargesTotal + $outstandingBefore, 2),
        ]];
    }
}
