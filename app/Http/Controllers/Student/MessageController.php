<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * The student's inbox — messages addressed to them, newest first.
     */
    public function index(Request $request): View
    {
        $messages = $request->user()->receivedMessages()
            ->with('sender')
            ->latest()
            ->paginate(15);

        return view('student.messages.index', compact('messages'));
    }

    /**
     * Read a single message. Opening it marks it as read.
     */
    public function show(Message $message): View
    {
        $this->ensureOwner($message);

        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('student.messages.show', compact('message'));
    }

    /**
     * A student may only ever read messages addressed to them.
     */
    private function ensureOwner(Message $message): void
    {
        abort_unless($message->receiver_id === request()->user()->id, 403);
    }
}
