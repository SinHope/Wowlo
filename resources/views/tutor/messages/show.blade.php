<x-app-layout>
    <x-slot name="header">Message</x-slot>

    <div class="mx-auto max-w-2xl space-y-5">

        @php $received = $message->receiver_id === auth()->id(); @endphp

        <div class="flex items-center gap-3">
            <a href="{{ $received ? route('tutor.messages.inbox') : route('tutor.messages.index') }}" class="text-muted hover:text-ink" aria-label="Back">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </a>
            <h2 class="text-2xl font-extrabold text-ink">Message</h2>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-xl font-bold text-ink">{{ $message->subject }}</h3>
                @unless ($received)
                    @if ($message->is_read)
                        <span class="rounded-full bg-success/10 px-2 py-0.5 text-xs font-semibold text-success">Read by student</span>
                    @else
                        <span class="rounded-full bg-amber/10 px-2 py-0.5 text-xs font-semibold text-amber-600">Not read yet</span>
                    @endif
                @endunless
            </div>
            <p class="mt-1 text-sm text-muted">
                @if ($received)
                    From {{ $message->sender->name }} · {{ $message->created_at->format('d M Y, g:i A') }}
                @else
                    To {{ $message->receiver->name }} · {{ $message->created_at->format('d M Y, g:i A') }}
                @endif
            </p>

            <hr class="my-4 border-gray-100">

            <div class="whitespace-pre-line text-sm leading-relaxed text-ink">{{ $message->body }}</div>
        </div>
    </div>
</x-app-layout>
