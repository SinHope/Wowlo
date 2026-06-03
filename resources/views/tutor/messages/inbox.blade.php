<x-app-layout>
    <x-slot name="header">Inbox</x-slot>

    <div class="mx-auto max-w-5xl space-y-5">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Inbox</h2>
                <p class="text-muted">{{ $messages->total() }} received · notices from the admin (e.g. exam-paper approvals)</p>
            </div>
            <a href="{{ route('tutor.messages.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink transition-colors duration-200 hover:bg-gray-50 cursor-pointer">
                Sent messages
            </a>
        </div>

        @forelse ($messages as $message)
            <a href="{{ route('tutor.messages.show', $message) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate font-bold text-ink">{{ $message->subject }}</p>
                        @unless ($message->is_read)
                            <span class="rounded-full bg-amber/10 px-2 py-0.5 text-xs font-semibold text-amber-600">New</span>
                        @endunless
                    </div>
                    <p class="mt-0.5 truncate text-sm text-muted">
                        From {{ $message->sender->name }} · {{ $message->created_at->format('d M Y, g:i A') }}
                    </p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">Your inbox is empty</p>
                <p class="mt-1 text-sm text-muted">Approval notices and admin messages will appear here.</p>
            </div>
        @endforelse

        @if ($messages->hasPages())
            <div>{{ $messages->links() }}</div>
        @endif
    </div>
</x-app-layout>
