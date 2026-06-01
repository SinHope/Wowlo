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
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
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
     * Toggle this homework between done / pending.
     */
    public function toggle(Homework $homework): RedirectResponse
    {
        $this->ensureOwner($homework);

        $done = ! $homework->isDone();
        $homework->update([
            'status' => $done ? 'done' : 'pending',
            'completed_at' => $done ? now() : null,
        ]);

        return back()->with('status', $done ? 'Marked as done. 🎉' : 'Marked as not done.');
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
