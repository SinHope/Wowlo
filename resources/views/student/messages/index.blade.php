<x-app-layout>
    <x-slot name="header">Messages</x-slot>

    <div class="mx-auto max-w-3xl space-y-5">

        <div>
            <h2 class="text-2xl font-extrabold text-ink">Messages</h2>
            <p class="text-muted">Messages from your tutor. Tap one to read it.</p>
        </div>

        @forelse ($messages as $message)
            <a href="{{ route('student.messages.show', $message) }}"
               @class([
                   'flex items-center gap-4 rounded-2xl border p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer',
                   'border-primary/30 bg-primary/5' => ! $message->is_read,
                   'border-gray-100 bg-white' => $message->is_read,
               ])>
                @unless ($message->is_read)
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-primary" title="Unread"></span>
                @endunless
                <div class="min-w-0 flex-1">
                    <p @class(['truncate text-ink', 'font-extrabold' => ! $message->is_read, 'font-semibold' => $message->is_read])>
                        {{ $message->subject }}
                    </p>
                    <p class="mt-0.5 truncate text-sm text-muted">
                        From {{ $message->sender->name }} · {{ $message->created_at->format('d M Y') }}
                    </p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No messages yet</p>
                <p class="mt-1 text-sm text-muted">Messages from your tutor will appear here.</p>
            </div>
        @endforelse

        @if ($messages->hasPages())
            <div>{{ $messages->links() }}</div>
        @endif
    </div>
</x-app-layout>
