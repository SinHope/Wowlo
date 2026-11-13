<x-app-layout>
    <x-slot name="header">Messages</x-slot>

    <div class="mx-auto max-w-3xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Messages</h2>
                <p class="text-muted">Messages between you and your tutor. Tap one to read it.</p>
            </div>
            @if ($quota)
                <div class="text-right">
                    <a href="{{ route('student.messages.create') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        Message my tutor
                    </a>
                    <p @class([
                        'mt-1 text-xs font-semibold',
                        'text-danger' => $quota['remaining'] === 0,
                        'text-muted' => $quota['remaining'] > 0,
                    ])>{{ $quota['remaining'] }} of {{ $quota['limit'] }} messages left this hour</p>
                </div>
            @endif
        </div>

        <!-- Inbox / Sent tabs -->
        <div class="flex gap-2">
            @foreach (['inbox' => 'Inbox', 'sent' => 'Sent'] as $key => $label)
                <a href="{{ route('student.messages.index', $key === 'sent' ? ['box' => 'sent'] : []) }}"
                   @class([
                       'inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold transition-colors duration-200 cursor-pointer',
                       'bg-primary text-white' => $box === $key,
                       'bg-white text-muted border border-gray-200 hover:text-ink' => $box !== $key,
                   ])>
                    {{ $label }}
                    @if ($key === 'inbox' && $unread > 0)
                        <span @class([
                            'rounded-full px-1.5 text-xs font-bold',
                            'bg-white text-primary' => $box === $key,
                            'bg-primary text-white' => $box !== $key,
                        ])>{{ $unread }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        @forelse ($messages as $message)
            @php $unreadByMe = $box === 'inbox' && ! $message->is_read; @endphp
            <a href="{{ route('student.messages.show', $message) }}"
               @class([
                   'flex items-center gap-4 rounded-2xl border p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer',
                   'border-primary/30 bg-primary/5' => $unreadByMe,
                   'border-gray-100 bg-white' => ! $unreadByMe,
               ])>
                @if ($unreadByMe)
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-primary" title="Unread"></span>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p @class(['truncate text-ink', 'font-extrabold' => $unreadByMe, 'font-semibold' => ! $unreadByMe])>
                            {{ $message->subject }}
                        </p>
                        @if ($box === 'sent')
                            @if ($message->is_read)
                                <span class="rounded-full bg-success/10 px-2 py-0.5 text-xs font-semibold text-success">Read</span>
                            @else
                                <span class="rounded-full bg-accent/15 px-2 py-0.5 text-xs font-semibold text-accent-dark">Not read yet</span>
                            @endif
                        @endif
                    </div>
                    <p class="mt-0.5 truncate text-sm text-muted">
                        @if ($box === 'sent')
                            To {{ $message->receiver->name }} · {{ $message->created_at->format('d M Y, g:i A') }}
                        @else
                            From {{ $message->sender->name }} · {{ $message->created_at->format('d M Y') }}
                        @endif
                    </p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                @if ($box === 'sent')
                    <p class="text-lg font-bold text-ink">You haven't sent any messages yet</p>
                    <p class="mt-1 text-sm text-muted">Messages you send to your tutor will appear here.</p>
                @else
                    <p class="text-lg font-bold text-ink">No messages yet</p>
                    <p class="mt-1 text-sm text-muted">Messages from your tutor will appear here.</p>
                @endif
            </div>
        @endforelse

        @if ($messages->hasPages())
            <div>{{ $messages->links() }}</div>
        @endif
    </div>
</x-app-layout>
