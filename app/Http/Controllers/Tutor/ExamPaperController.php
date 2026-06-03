<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamPaperRequest;
use App\Models\ExamPaper;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exam papers are a SHARED, MODERATED library.
 *
 * - Approved papers are visible to (and downloadable by) every tutor + student.
 * - A regular tutor's upload is a `pending` submission until the super_admin
 *   approves it. Approval/rejection notifies the submitting tutor via a Message
 *   (which lands in their tutor Inbox).
 * - The super_admin's own uploads are approved immediately.
 */
class ExamPaperController extends Controller
{
    public function index(): View
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        // The shared library = approved papers, grouped Level → Subject → Year.
        $approved = ExamPaper::approved()->orderByDesc('year')->orderByDesc('id')->get();
        $grouped  = ExamPaper::groupForDisplay($approved);

        // Pending submissions: the super_admin sees ALL (the approval queue);
        // a regular tutor sees only their own ("awaiting approval").
        $pending = ExamPaper::pending()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('tutor_id', auth()->id()))
            ->with('tutor')
            ->latest()
            ->get();

        return view('tutor.exam-papers.index', [
            'grouped'      => $grouped,
            'total'        => $approved->whereNotNull('level')->count(),
            'pending'      => $pending,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function create(): View
    {
        return view('tutor.exam-papers.create', [
            'levels'       => config('wowlo.levels'),
            'subjects'     => config('wowlo.subjects'),
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
        ]);
    }

    public function store(ExamPaperRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');

        $data['file_path']         = $file->store('exam-papers', 'r2');
        $data['original_filename'] = $file->getClientOriginalName();
        $data['tutor_id']          = $request->user()->id;
        unset($data['file']);

        if ($request->user()->isSuperAdmin()) {
            // Super_admin uploads go live immediately.
            $data['status']      = 'approved';
            $data['approved_by'] = $request->user()->id;
            $data['approved_at'] = now();
            $status = 'Exam paper uploaded.';
        } else {
            // Tutor uploads queue for the super_admin's approval.
            $data['status'] = 'pending';
            $status = 'Sent to super admin for approval.';
        }

        ExamPaper::create($data);

        return redirect()->route('tutor.exam-papers.index')->with('status', $status);
    }

    /**
     * Approve a pending submission (super_admin only) and notify the uploader.
     */
    public function approve(ExamPaper $examPaper): RedirectResponse
    {
        $this->ensureSuperAdmin();

        if ($examPaper->isApproved()) {
            return back()->with('status', 'That paper is already approved.');
        }

        $examPaper->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->notifyUploader(
            $examPaper,
            'Exam paper approved',
            "Your exam paper \"{$examPaper->title}\" has been approved and added to the shared library.",
        );

        return back()->with('status', 'Paper approved — the tutor has been notified.');
    }

    /**
     * Reject a pending submission (super_admin only): delete the file + row and
     * notify the uploader (with an optional reason).
     */
    public function reject(Request $request, ExamPaper $examPaper): RedirectResponse
    {
        $this->ensureSuperAdmin();

        abort_unless($examPaper->isPending(), 404);

        $reason = trim((string) $request->input('reason'));
        $body = "Your exam paper \"{$examPaper->title}\" was not approved.";
        if ($reason !== '') {
            $body .= " Reason: {$reason}";
        }

        // Capture before deletion so the notice still has the uploader + title.
        $this->notifyUploader($examPaper, 'Exam paper not approved', $body);

        Storage::disk('r2')->delete($examPaper->file_path);
        $examPaper->delete();

        return back()->with('status', 'Paper rejected — the tutor has been notified.');
    }

    public function destroy(ExamPaper $examPaper): RedirectResponse
    {
        // Super_admin may remove any paper from the shared library; a regular
        // tutor may only withdraw their own still-pending submission.
        $canDelete = auth()->user()->isSuperAdmin()
            || ($examPaper->isPending() && $examPaper->tutor_id === auth()->id());

        abort_unless($canDelete, 404);

        Storage::disk('r2')->delete($examPaper->file_path);
        $examPaper->delete();

        return redirect()->route('tutor.exam-papers.index')
            ->with('status', 'Exam paper deleted.');
    }

    public function download(ExamPaper $examPaper): StreamedResponse
    {
        // Anyone in the workspace can download an approved paper; otherwise only
        // the super_admin or the uploader (their own pending submission).
        $canDownload = $examPaper->isApproved()
            || auth()->user()->isSuperAdmin()
            || $examPaper->tutor_id === auth()->id();

        abort_unless($canDownload, 404);

        return Storage::disk('r2')->download($examPaper->file_path, $examPaper->original_filename);
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
    }

    /**
     * Send the submitting tutor an in-app message (lands in their Inbox) plus a
     * best-effort push. Skips self-notification for the super_admin's own uploads.
     */
    private function notifyUploader(ExamPaper $examPaper, string $subject, string $body): void
    {
        if ($examPaper->tutor_id === auth()->id()) {
            return;
        }

        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $examPaper->tutor_id,
            'subject'     => $subject,
            'body'        => $body,
        ]);

        try {
            $message->receiver?->notify(new \App\Notifications\NewMessageNotification($message));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
