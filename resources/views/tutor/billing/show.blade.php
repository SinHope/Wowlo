<x-app-layout>
    <x-slot name="header">Bill · {{ $bill->student->name }}</x-slot>

    @php $cur = $bill->currency; @endphp

    <div class="mx-auto max-w-2xl space-y-5" x-data="{ copied: false, deleteOpen: false }">

        <div class="flex items-center gap-3">
            <a href="{{ route('tutor.billing.index') }}" class="text-muted hover:text-ink" aria-label="Back">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </a>
            <h2 class="text-2xl font-extrabold text-ink">Bill — {{ $bill->billing_month->format('F Y') }}</h2>
        </div>

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <!-- Summary -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-bold text-ink">{{ $bill->student->name }}</p>
                    <p class="text-sm text-muted">{{ $bill->billing_month->format('F Y') }} · {{ $bill->lines->count() }} {{ Str::plural('lesson', $bill->lines->count()) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-muted">Grand total</p>
                    <p class="text-2xl font-extrabold text-primary-dark">{{ $cur }} {{ number_format($bill->grand_total, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Copyable message -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-ink">WhatsApp Message</h3>
                <button type="button"
                        @click="navigator.clipboard.writeText($refs.msg.textContent).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
            <pre x-ref="msg" class="mt-3 whitespace-pre-wrap rounded-xl bg-cream p-4 font-sans text-sm leading-relaxed text-ink">{{ $message }}</pre>
        </div>

        <!-- Edit / delete -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('tutor.billing.edit', $bill) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-amber-700 cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                Edit
            </a>
            <button type="button" @click="deleteOpen = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-danger px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-red-700 cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                Delete
            </button>
        </div>

        <!-- Delete confirm -->
        <div x-show="deleteOpen" x-cloak x-transition.opacity
             @keydown.escape.window="deleteOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true">
            <div @click.outside="deleteOpen = false" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-ink">Delete this bill?</h3>
                <p class="mt-2 text-sm text-muted">This can't be undone. The student's outstanding balance will be recalculated without it.</p>
                <form method="POST" action="{{ route('tutor.billing.destroy', $bill) }}" class="mt-5 flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteOpen = false"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-ink hover:bg-gray-200 cursor-pointer">Cancel</button>
                    <button type="submit"
                            class="rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 cursor-pointer">Yes, delete</button>
                </form>
            </div>
        </div>

        <a href="{{ route('tutor.billing.create') }}"
           class="block text-center text-sm font-semibold text-primary-dark hover:underline">+ Generate another bill</a>
    </div>
    <style>[x-cloak]{display:none!important;}</style>
</x-app-layout>
