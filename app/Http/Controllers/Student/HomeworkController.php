<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeworkController extends Controller
{
    public function index(Request $request): View
    {
        $homework = $request->user()->homework()
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderBy('due_date')
            ->paginate(15);

        return view('student.homework.index', compact('homework'));
    }

    public function show(Homework $homework): View
    {
        $this->ensureOwner($homework);

        return view('student.homework.show', compact('homework'));
    }

    /**
     * Student claims "I've done this" (→ submitted, awaiting the tutor's check),
     * or retracts the claim (→ pending). The student can NEVER set the
     * authoritative done / not_done verdict — that's the tutor's call. Once the
     * tutor has ruled 'done', the claim is locked.
     */
    public function submit(Homework $homework): RedirectResponse
    {
        $this->ensureOwner($homework);

        // A tutor-confirmed 'done' is final; the student can't reopen it.
        if ($homework->isDone()) {
            return back()->with('status', 'Your tutor has already marked this done.');
        }

        $claiming = ! $homework->isSubmitted();
        $homework->update(['status' => $claiming ? 'submitted' : 'pending']);

        return back()->with('status', $claiming
            ? "Sent to your tutor to check. ✋"
            : 'Claim withdrawn — back to not done yet.');
    }

    public function download(Homework $homework): StreamedResponse
    {
        $this->ensureOwner($homework);
        abort_unless($homework->hasAttachment(), 404);

        return Storage::disk('r2')->download($homework->attachment_path, $homework->attachment_name);
    }

    /**
     * A student may only ever touch their own homework.
     */
    private function ensureOwner(Homework $homework): void
    {
        abort_unless($homework->student_id === request()->user()->id, 403);
    }
}
