<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MultiplicationAttempt;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Multiplication Rabbit — tutor side (see docs/multiplication-game.md).
 *
 * A tutor reviews ONLY their own students' completed rounds and leaves feedback
 * the student then sees on their results page. Tenancy: every record is scoped
 * by the acting tutor (tutor_id); anything else 404s.
 */
class MultiplicationController extends Controller
{
    /** All of this tutor's students' rounds, newest first. */
    public function index(): View
    {
        $attempts = MultiplicationAttempt::where('tutor_id', auth()->id())
            ->with('student')
            ->latest('id')
            ->paginate(20);

        return view('tutor.games.multiplication.index', compact('attempts'));
    }

    /** One round (tutor writes feedback here). */
    public function show(MultiplicationAttempt $attempt): View
    {
        $this->ensureOwned($attempt);
        $attempt->load('student', 'feedbackBy');

        return view('tutor.games.multiplication.show', compact('attempt'));
    }

    /** Save the tutor's feedback and notify the student. */
    public function feedback(Request $request, MultiplicationAttempt $attempt): RedirectResponse
    {
        $this->ensureOwned($attempt);

        $validated = $request->validate([
            'feedback' => ['required', 'string', 'max:5000'],
        ]);

        $attempt->update([
            'feedback'    => $validated['feedback'],
            'feedback_by' => auth()->id(),
            'feedback_at' => now(),
        ]);

        $this->notifyStudent($attempt);

        return redirect()->route('tutor.games.multiplication.show', $attempt)
            ->with('status', 'Feedback sent to your student.');
    }

    /** Guard: the attempt must belong to the acting tutor (404, not 403). */
    private function ensureOwned(MultiplicationAttempt $attempt): void
    {
        abort_unless($attempt->tutor_id === auth()->id(), 404);
    }

    /** Let the student know feedback is waiting (inbox + best-effort push). */
    private function notifyStudent(MultiplicationAttempt $attempt): void
    {
        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $attempt->student_id,
            'subject'     => "Multiplication feedback: {$attempt->level}",
            'body'        => auth()->user()->name . " left feedback on your {$attempt->level} multiplication round.",
        ]);

        try {
            $message->receiver?->notify(new NewMessageNotification($message));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
