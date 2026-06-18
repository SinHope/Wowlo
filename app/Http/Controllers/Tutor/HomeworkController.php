<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomeworkRequest;
use App\Models\Homework;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeworkController extends Controller
{
    public function index(): View
    {
        // Tenancy: only homework this teacher created.
        $homework = Homework::where('tutor_id', auth()->id())
            ->with('student')
            ->latest('created_at')
            ->paginate(15);

        return view('tutor.homework.index', compact('homework'));
    }

    public function create(): View
    {
        return view('tutor.homework.create', [
            'students' => $this->students(),
        ]);
    }

    public function store(HomeworkRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Tenancy: the assignee must be one of this teacher's own students.
        $request->user()->students()->findOrFail($data['student_id']);
        $data['tutor_id'] = $request->user()->id;
        $data = array_merge($data, $this->handleUpload($request));

        $homework = Homework::create($data);

        // Best-effort push to the assigned student; never break the request.
        try {
            $homework->student?->notify(new \App\Notifications\NewHomeworkNotification($homework));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('tutor.homework.index')
            ->with('status', 'Homework assigned.');
    }

    public function edit(Homework $homework): View
    {
        $this->ensureOwned($homework);

        return view('tutor.homework.edit', [
            'homework' => $homework,
            'students' => $this->students(),
        ]);
    }

    public function update(HomeworkRequest $request, Homework $homework): RedirectResponse
    {
        $this->ensureOwned($homework);

        $data = $request->validated();
        // If the assignee is being changed, it must still be one of this teacher's students.
        $request->user()->students()->findOrFail($data['student_id']);

        if ($request->hasFile('attachment')) {
            $this->deleteAttachment($homework);
            $data = array_merge($data, $this->handleUpload($request));
        } else {
            unset($data['attachment']);
        }

        $homework->update($data);

        return redirect()->route('tutor.homework.index')
            ->with('status', 'Homework updated.');
    }

    public function destroy(Homework $homework): RedirectResponse
    {
        $this->ensureOwned($homework);

        $this->deleteAttachment($homework);
        $homework->delete();

        return redirect()->route('tutor.homework.index')
            ->with('status', 'Homework deleted.');
    }

    /**
     * Homework completion overview, optionally filtered to one student.
     */
    public function status(Request $request): View
    {
        $students = $this->students();
        $selectedId = $request->integer('student_id') ?: null;

        // Tenancy: only this teacher's own homework, for their own students.
        $homework = $selectedId
            ? Homework::where('tutor_id', auth()->id())->where('student_id', $selectedId)->latest('due_date')->get()
            : collect();

        return view('tutor.homework.status', compact('students', 'selectedId', 'homework'));
    }

    /**
     * The tutor's authoritative verdict on one homework, plus an editable
     * feedback note. Only done / not_done can be set here — the student can
     * never reach this. Ownership-guarded (404 so IDs don't leak).
     */
    public function verdict(Request $request, Homework $homework): RedirectResponse
    {
        $this->ensureOwned($homework);

        $data = $request->validate([
            'status'   => ['required', Rule::in(['done', 'not_done'])],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $homework->update([
            'status'       => $data['status'],
            'completed_at' => $data['status'] === 'done' ? now() : null,
            'feedback'     => $data['feedback'] ?? null,
        ]);

        return redirect()
            ->route('tutor.homework.status', ['student_id' => $homework->student_id])
            ->with('status', $data['status'] === 'done' ? 'Marked done.' : 'Marked not done.');
    }

    /**
     * Stream an attachment from R2 (only the owning teacher's homework).
     */
    public function download(Homework $homework): StreamedResponse
    {
        $this->ensureOwned($homework);
        abort_unless($homework->hasAttachment(), 404);

        return Storage::disk('r2')->download($homework->attachment_path, $homework->attachment_name);
    }

    /**
     * This teacher's own students for the assignee dropdown.
     */
    private function students()
    {
        return auth()->user()->students()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Guard: the homework must belong to the acting teacher.
     */
    private function ensureOwned(Homework $homework): void
    {
        abort_unless($homework->tutor_id === auth()->id(), 404);
    }

    /**
     * Store an uploaded file on R2 and return the columns to persist.
     */
    private function handleUpload(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        $file = $request->file('attachment');

        return [
            'attachment_path' => $file->store('homework', 'r2'),
            'attachment_name' => $file->getClientOriginalName(),
        ];
    }

    private function deleteAttachment(Homework $homework): void
    {
        if ($homework->hasAttachment()) {
            Storage::disk('r2')->delete($homework->attachment_path);
        }
    }
}
