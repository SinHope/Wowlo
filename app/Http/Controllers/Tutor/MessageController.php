<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * List of messages the tutor has sent, newest first.
     */
    public function index(): View
    {
        $messages = Message::with('receiver')
            ->latest()
            ->paginate(15);

        return view('tutor.messages.index', compact('messages'));
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
        $data['sender_id'] = $request->user()->id;

        Message::create($data);

        return redirect()->route('tutor.messages.index')
            ->with('status', 'Message sent.');
    }

    public function show(Message $message): View
    {
        return view('tutor.messages.show', compact('message'));
    }

    /**
     * All student accounts for the recipient dropdown.
     */
    private function students()
    {
        return User::where('role', 'student')->orderBy('name')->get(['id', 'name']);
    }
}
