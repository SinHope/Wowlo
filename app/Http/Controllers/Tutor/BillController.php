<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\BillRequest;
use App\Models\Bill;
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
            'students' => $students,
            'currency' => config('wowlo.currency'),
            'paynow' => config('wowlo.paynow_number'),
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

        // Authoritative recompute.
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
        $outstandingBefore = Ledger::outstanding($student);   // snapshot before this bill
        $grandTotal = round($chargesTotal + $outstandingBefore, 2);

        $billingMonth = Carbon::createFromFormat('Y-m', $data['billing_month'])->startOfMonth();

        $bill = DB::transaction(function () use (
            $request, $student, $billingMonth, $lessons, $charges,
            $lessonsSubtotal, $additionalTotal, $chargesTotal, $outstandingBefore, $grandTotal
        ) {
            $bill = Bill::create([
                'student_id' => $student->id,
                'tutor_id' => $request->user()->id,
                'billing_month' => $billingMonth,
                'lessons_subtotal' => $lessonsSubtotal,
                'additional_total' => $additionalTotal,
                'charges_total' => $chargesTotal,
                'outstanding_before' => $outstandingBefore,
                'grand_total' => $grandTotal,
                'currency' => config('wowlo.currency'),
            ]);

            $bill->lines()->createMany($lessons->all());

            if ($charges->isNotEmpty()) {
                $bill->charges()->createMany($charges->all());
            }

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
}
