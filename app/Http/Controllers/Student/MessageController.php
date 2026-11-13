<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * The student's messages, newest first — Inbox (from their tutor) or
     * Sent (to their tutor), switched by ?box=sent.
     */
    public function index(Request $request): View
    {
        $student = $request->user();
        $box = $request->query('box') === 'sent' ? 'sent' : 'inbox';

        $messages = ($box === 'sent'
                ? $student->sentMessages()->with('receiver')
                : $student->receivedMessages()->with('sender'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('student.messages.index', [
            'messages' => $messages,
            'box' => $box,
            'unread' => $student->receivedMessages()->where('is_read', false)->count(),
            'quota' => $student->tutor_id ? $this->quota($student) : null,
        ]);
    }

    /**
     * Compose a message to the student's own tutor. ?reply_to={id} prefills
     * "Re: …" from a message the student received (prefill only — the
     * recipient is always their tutor regardless).
     */
    public function create(Request $request): View
    {
        $student = $request->user();
        abort_unless($student->tutor_id, 404);

        $replyTo = $request->filled('reply_to')
            ? $student->receivedMessages()->find($request->integer('reply_to'))
            : null;

        return view('student.messages.create', [
            'tutor' => $student->tutor,
            'replyTo' => $replyTo,
            'quota' => $this->quota($student),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $request->user();
        abort_unless($student->tutor_id, 404);

        // No receiver_id rule: anything the client sends for it is dropped.
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        // Anti-spam: N messages per hour, counted only after validation passes.
        $limit = $this->limit();
        if (RateLimiter::tooManyAttempts($this->limiterKey($student), $limit)) {
            return back()->withInput()->withErrors([
                'limit' => "You've used all {$limit} messages for this hour.",
            ]);
        }
        RateLimiter::hit($this->limiterKey($student), 3600);

        $message = Message::create([
            'sender_id' => $student->id,
            // Tenancy: a student can only ever message their own tutor — set
            // server-side, never from the form.
            'receiver_id' => $student->tutor_id,
            'subject' => $data['subject'],
            'body' => $data['body'],
        ]);

        // Best-effort push to the tutor; never break the request.
        try {
            $message->receiver?->notify(new NewMessageNotification($message));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('student.messages.index', ['box' => 'sent'])
            ->with('status', 'Message sent to ' . $message->receiver->name . '.');
    }

    /**
     * Read a single message (received or sent). Opening a received one marks
     * it as read; viewing your own sent message never does.
     */
    public function show(Message $message): View
    {
        $this->ensureParty($message);

        if ($message->receiver_id === request()->user()->id && ! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('student.messages.show', compact('message'));
    }

    /**
     * A student may only ever see messages they sent or received.
     */
    private function ensureParty(Message $message): void
    {
        $id = request()->user()->id;

        abort_unless($message->receiver_id === $id || $message->sender_id === $id, 403);
    }

    /**
     * How many messages the student can still send this hour, and when the
     * hour's allowance refills (null until they've sent one).
     *
     * @return array{limit: int, remaining: int, resets_at: ?\Illuminate\Support\Carbon}
     */
    private function quota(User $student): array
    {
        $key = $this->limiterKey($student);
        $limit = $this->limit();
        $remaining = max(0, RateLimiter::remaining($key, $limit));

        return [
            'limit' => $limit,
            'remaining' => $remaining,
            'resets_at' => $remaining < $limit ? now()->addSeconds(RateLimiter::availableIn($key)) : null,
        ];
    }

    private function limit(): int
    {
        return (int) config('wowlo.student_messages_per_hour');
    }

    private function limiterKey(User $student): string
    {
        return 'student-messages:' . $student->id;
    }
}
