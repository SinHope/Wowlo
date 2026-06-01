<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FeeController extends Controller
{
    /**
     * The read-only fee page (only reachable once unlocked).
     */
    public function index(Request $request): View
    {
        $student = $request->user();
        $student->load(['tuitionFee', 'payments' => fn ($q) => $q->latest('payment_date')->latest('id')]);

        return view('student.fees.index', [
            'fee' => $student->tuitionFee,
            'payments' => $student->payments,
            'outstanding' => Ledger::outstanding($student),
        ]);
    }

    /**
     * Show the parent password prompt (skip if already unlocked).
     */
    public function unlock(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('fee_unlocked')) {
            return redirect()->route('student.fees.index');
        }

        return view('student.fees.unlock');
    }

    /**
     * Verify the shared fee password and unlock for this session.
     */
    public function attemptUnlock(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $expected = config('wowlo.fee_view_password');

        // Constant-time compare; falls back to false if no password configured.
        if (is_string($expected) && $expected !== '' && hash_equals($expected, $request->input('password'))) {
            $request->session()->put('fee_unlocked', true);

            return redirect()->route('student.fees.index');
        }

        return back()->withErrors(['password' => 'Incorrect password.']);
    }

    /**
     * Re-lock the fee section.
     */
    public function lock(Request $request): RedirectResponse
    {
        $request->session()->forget('fee_unlocked');

        return redirect()->route('dashboard')->with('status', 'Fee section locked.');
    }
}
