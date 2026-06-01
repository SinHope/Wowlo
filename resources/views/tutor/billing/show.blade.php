<x-app-layout>
    <x-slot name="header">Bill · {{ $bill->student->name }}</x-slot>

    @php $cur = $bill->currency; @endphp

    <div class="mx-auto max-w-2xl space-y-5" x-data="{ copied: false }">

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

        <a href="{{ route('tutor.billing.create') }}"
           class="block text-center text-sm font-semibold text-primary-dark hover:underline">+ Generate another bill</a>
    </div>
</x-app-layout>
