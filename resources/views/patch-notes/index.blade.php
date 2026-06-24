<x-app-layout>
    <x-slot name="header">Patch Notes</x-slot>

    @php($isSuperAdmin = auth()->user()->isSuperAdmin())

    <div class="mx-auto max-w-3xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">What's new</h2>
                <p class="text-muted">Updates and improvements to Wowlo.</p>
            </div>
            @if ($isSuperAdmin)
                <a href="{{ route('admin.patch-notes.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Note
                </a>
            @endif
        </div>

        @forelse ($notes as $note)
            <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($note->version)
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary-dark">{{ $note->version }}</span>
                    @endif
                    <span class="text-xs text-muted">{{ $note->created_at->format('j M Y') }}</span>
                </div>

                <h3 class="mt-2 text-xl font-extrabold text-ink">{{ $note->title }}</h3>

                {{-- Sanitised allowlist HTML (see App\Support\HtmlSanitizer). --}}
                <div class="prose-wowlo mt-3 text-sm leading-relaxed text-ink [&_a]:font-semibold [&_a]:text-primary-dark [&_a]:underline [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-6">
                    {!! $note->body !!}
                </div>

                @if ($note->hasImage())
                    <img src="{{ route('patch-notes.image', $note) }}" alt="{{ $note->title }}"
                         class="mt-4 max-h-96 w-full rounded-xl border border-gray-100 object-contain">
                @endif

                @if ($isSuperAdmin)
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('admin.patch-notes.edit', $note) }}"
                           class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Edit</a>
                        <form method="POST" action="{{ route('admin.patch-notes.destroy', $note) }}"
                              onsubmit="return confirm('Delete this patch note for good?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="rounded-lg border border-danger/30 px-3 py-1.5 text-sm font-semibold text-danger hover:bg-danger/10 cursor-pointer">Delete</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No patch notes yet</p>
                <p class="mt-1 text-sm text-muted">Updates to the app will show up here.</p>
                @if ($isSuperAdmin)
                    <a href="{{ route('admin.patch-notes.create') }}"
                       class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        New Note
                    </a>
                @endif
            </div>
        @endforelse

        @if ($notes->hasPages())
            <div>{{ $notes->links() }}</div>
        @endif
    </div>
</x-app-layout>
