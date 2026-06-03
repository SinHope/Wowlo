<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeeRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Payment;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FinanceController extends Controller
{
    /**
     * Overview: every student with their hourly rate and current outstanding.
     */
    public function index(): View
    {
        $students = auth()->user()->students()
            ->with(['tuitionFee'])
            ->orderBy('name')
            ->get()
            ->map(function (User $student) {
                $student->outstanding = Ledger::outstanding($student);

                return $student;
            });

        return view('tutor.finance.index', compact('students'));
    }

    /**
     * One student's finance detail: fee rate, payments, outstanding.
     */
    public function show(User $student): View
    {
        $this->ensureStudent($student);

        $student->load(['tuitionFee', 'payments' => fn ($q) => $q->latest('payment_date')->latest('id')]);

        return view('tutor.finance.show', [
            'student' => $student,
            'fee' => $student->tuitionFee,
            'payments' => $student->payments,
            'totalBilled' => Ledger::totalBilled($student),
            'totalPaid' => Ledger::totalPaid($student),
            'outstanding' => Ledger::outstanding($student),
        ]);
    }

    /**
     * Create or update the student's hourly rate (one fee row per student).
     */
    public function saveFee(FeeRequest $request, User $student): RedirectResponse
    {
        $this->ensureStudent($student);

        $student->tuitionFee()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'fee_rate_per_hour' => $request->validated('fee_rate_per_hour'),
                'remarks' => $request->validated('remarks'),
                'currency' => config('wowlo.currency'),
            ],
        );

        return back()->with('status', 'Fee rate saved.');
    }

    /**
     * Record a payment received from the student.
     */
    public function storePayment(PaymentRequest $request, User $student): RedirectResponse
    {
        $this->ensureStudent($student);

        $student->payments()->create($request->validated());

        return back()->with('status', 'Payment recorded.');
    }

    /**
     * Remove a payment (correcting a mistake).
     */
    public function destroyPayment(Payment $payment): RedirectResponse
    {
        // Tenancy: the payment's student must belong to the acting teacher.
        $this->ensureStudent($payment->student);

        $studentId = $payment->student_id;
        $payment->delete();

        return redirect()
            ->route('tutor.finance.show', $studentId)
            ->with('status', 'Payment deleted.');
    }

    /**
     * Guard: a student owned by the acting teacher. 404 hides other rosters.
     */
    private function ensureStudent(?User $student): void
    {
        abort_unless($student && $student->isStudent() && $student->tutor_id === auth()->id(), 404);
    }
}
