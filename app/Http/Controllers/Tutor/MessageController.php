<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Messages this teacher has SENT, newest first.
     */
    public function index(): View
    {
        $messages = Message::where('sender_id', auth()->id())
            ->with('receiver')
            ->latest()
            ->paginate(15);

        return view('tutor.messages.index', compact('messages'));
    }

    /**
     * Messages this teacher has RECEIVED (e.g. exam-paper approval notices).
     */
    public function inbox(): View
    {
        $messages = Message::where('receiver_id', auth()->id())
            ->with('sender')
            ->latest()
            ->paginate(15);

        return view('tutor.messages.inbox', compact('messages'));
    }

    public function create(): View
    {
        return view('tutor.messages.create', [
            'students' => $this->students(),
        ]);
    }

    public function store(MessageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Tenancy: can only message one of this teacher's own students.
        $request->user()->students()->findOrFail($data['receiver_id']);
        $data['sender_id'] = $request->user()->id;

        $message = Message::create($data);

        // Best-effort push to the receiver; never break the request.
        try {
            $message->receiver?->notify(new \App\Notifications\NewMessageNotification($message));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('tutor.messages.index')
            ->with('status', 'Message sent.');
    }

    public function show(Message $message): View
    {
        // The acting teacher must be a party to this message (sender or receiver).
        abort_unless(
            $message->sender_id === auth()->id() || $message->receiver_id === auth()->id(),
            404,
        );

        // Reading a received message marks it read (drives the Inbox unread badge).
        if ($message->receiver_id === auth()->id() && ! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('tutor.messages.show', compact('message'));
    }

    /**
     * This teacher's own students for the recipient dropdown.
     */
    private function students()
    {
        return auth()->user()->students()->orderBy('name')->get(['id', 'name']);
    }
}
