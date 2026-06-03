<x-app-layout>
    <x-slot name="header">Exam Papers</x-slot>

    <div class="mx-auto max-w-5xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex items-center justify-between"
             x-data="spinner('Loading…')">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Exam Papers</h2>
                <p class="text-muted">
                    Shared library · {{ $total }} approved {{ Str::plural('paper', $total) }} · {{ $grouped->count() }} {{ Str::plural('level', $grouped->count()) }}
                </p>
            </div>
            <a href="{{ route('tutor.exam-papers.create') }}" @click="start()"
               class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Upload Paper
            </a>
            <x-spinner-overlay />
        </div>

        {{-- Pending submissions: the super_admin's approval queue, or a tutor's
             own "awaiting approval" list. --}}
        @if ($pending->isNotEmpty())
            <div class="overflow-hidden rounded-2xl border border-amber-300 bg-amber-50 shadow-sm">
                <div class="border-b border-amber-200 bg-amber-100 px-5 py-3">
                    <h3 class="text-sm font-extrabold uppercase tracking-wide text-amber-700">
                        {{ $isSuperAdmin ? 'Pending approval (' . $pending->count() . ')' : 'Your submissions awaiting approval' }}
                    </h3>
                    @if ($isSuperAdmin)
                        <p class="text-xs text-amber-700/80">Submitted by other tutors — approve to add to the shared library.</p>
                    @else
                        <p class="text-xs text-amber-700/80">The admin will review these. You'll get an Inbox message once approved.</p>
                    @endif
                </div>
                <div class="divide-y divide-amber-100">
                    @foreach ($pending as $paper)
                        <div class="flex flex-wrap items-center gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-ink">{{ $paper->title }}</p>
                                <p class="truncate text-xs text-muted">
                                    {{ $paper->level }} · {{ $paper->subject }} · {{ $paper->year }}
                                    @if ($isSuperAdmin) · by {{ $paper->tutor?->name ?? 'Unknown' }} @endif
                                </p>
                            </div>
                            <a href="{{ route('tutor.exam-papers.download', $paper) }}"
                               class="inline-flex items-center gap-1 rounded-md border border-primary/30 px-2.5 py-1 text-xs font-semibold text-primary transition-colors hover:bg-primary/5 cursor-pointer">
                                Download
                            </a>
                            @if ($isSuperAdmin)
                                <form method="POST" action="{{ route('tutor.exam-papers.approve', $paper) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-md bg-success px-2.5 py-1 text-xs font-semibold text-white transition-colors hover:opacity-90 cursor-pointer">
                                        Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('tutor.exam-papers.reject', $paper) }}"
                                      onsubmit="return confirm('Reject and delete this submission? The tutor will be notified.')">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-md border border-danger/30 px-2.5 py-1 text-xs font-semibold text-danger transition-colors hover:bg-danger/5 cursor-pointer">
                                        Reject
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('tutor.exam-papers.destroy', $paper) }}"
                                      onsubmit="return confirm('Withdraw this submission?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-md border border-danger/30 px-2.5 py-1 text-xs font-semibold text-danger transition-colors hover:bg-danger/5 cursor-pointer">
                                        Withdraw
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @forelse ($grouped as $level => $subjects)
            <div x-data="{ open: true }" class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                {{-- Level header --}}
                <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between gap-3 bg-primary/5 px-5 py-4 text-left cursor-pointer hover:bg-primary/10">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                        </span>
                        <span class="text-lg font-extrabold text-ink">{{ $level }}</span>
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $subjects->flatten()->count() }}</span>
                    </div>
                    <svg class="h-5 w-5 text-primary transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                {{-- Subjects within this level --}}
                <div x-show="open" x-collapse.duration.200ms class="border-t border-gray-100 p-3 space-y-2">
                    @foreach ($subjects as $subject => $papers)
                        <div x-data="{ subOpen: true }" class="overflow-hidden rounded-xl border border-gray-100">
                            <button type="button" @click="subOpen = !subOpen"
                                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left cursor-pointer hover:bg-gray-50">
                                <div class="flex items-center gap-2.5">
                                    <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <span class="font-bold text-ink">{{ $subject }}</span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $papers->count() }}</span>
                                </div>
                                <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="subOpen && 'rotate-180'"
                                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            {{-- Years within this subject --}}
                            <div x-show="subOpen" x-collapse.duration.200ms class="border-t border-gray-100">
                                @foreach ($papers->groupBy('year') as $year => $yearPapers)
                                    <div class="px-4 py-2">
                                        <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-amber-600">{{ $year }}</p>
                                        <div class="space-y-1.5">
                                            @foreach ($yearPapers as $paper)
                                                <div class="flex items-center gap-3 rounded-lg bg-gray-50 px-3 py-2">
                                                    <p class="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{{ $paper->title }}</p>
                                                    <div class="flex shrink-0 items-center gap-2">
                                                        <a href="{{ route('tutor.exam-papers.download', $paper) }}"
                                                           class="inline-flex items-center gap-1 rounded-md border border-primary/30 px-2.5 py-1 text-xs font-semibold text-primary transition-colors hover:bg-primary/5 cursor-pointer">
                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                            </svg>
                                                            Download
                                                        </a>
                                                        @if ($isSuperAdmin)
                                                        <form method="POST" action="{{ route('tutor.exam-papers.destroy', $paper) }}"
                                                              onsubmit="return confirm('Delete this exam paper from the shared library? This cannot be undone.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="inline-flex items-center gap-1 rounded-md border border-danger/30 px-2.5 py-1 text-xs font-semibold text-danger transition-colors hover:bg-danger/5 cursor-pointer">
                                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                                Delete
                                                            </button>
                                                        </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No exam papers yet</p>
                <p class="mt-1 text-sm text-muted">Upload past year papers for students to download.</p>
                <a href="{{ route('tutor.exam-papers.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                    Upload First Paper
                </a>
            </div>
        @endforelse
    </div>
</x-app-layout>
