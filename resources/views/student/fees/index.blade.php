<x-app-layout>
    <x-slot name="header">Tuition Fee</x-slot>

    @php $cur = config('wowlo.currency'); @endphp

    <div class="mx-auto max-w-2xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Tuition Fee</h2>
                <p class="text-muted">Your rate, payments, and balance.</p>
            </div>
            <form method="POST" action="{{ route('student.fees.lock') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-muted hover:bg-gray-50 cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    Lock
                </button>
            </form>
        </div>

        <!-- Outstanding -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-muted">Outstanding Balance</p>
            @if ($outstanding > 0.001)
                <p class="mt-1 text-3xl font-extrabold text-danger">{{ $cur }} {{ number_format($outstanding, 2) }}</p>
                <p class="mt-1 text-sm text-muted">Amount due. Please arrange payment with your tutor.</p>
            @elseif ($outstanding < -0.001)
                <p class="mt-1 text-3xl font-extrabold text-success">{{ $cur }} {{ number_format(abs($outstanding), 2) }}</p>
                <p class="mt-1 text-sm text-muted">You are in credit (prepaid). 🎉</p>
            @else
                <p class="mt-1 text-3xl font-extrabold text-ink">All settled ✅</p>
                <p class="mt-1 text-sm text-muted">Nothing outstanding right now.</p>
            @endif
        </div>

        <!-- Rate -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-muted">Hourly Rate</p>
            @if ($fee)
                <p class="mt-1 text-xl font-bold text-ink">{{ $cur }} {{ number_format($fee->fee_rate_per_hour, 2) }} / hour</p>
                @if ($fee->remarks)
                    <p class="mt-1 text-sm text-muted">{{ $fee->remarks }}</p>
                @endif
            @else
                <p class="mt-1 text-sm text-muted">Your tutor hasn't set a rate yet.</p>
            @endif
        </div>

        <!-- Payment history -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-ink">Payment History</h3>
            @forelse ($payments as $payment)
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">{{ $cur }} {{ number_format($payment->amount_paid, 2) }}</p>
                        <p class="text-xs text-muted">
                            {{ $payment->payment_date->format('d M Y') }} · {{ $payment->methodLabel() }}
                            @if ($payment->remarks) · {{ $payment->remarks }} @endif
                        </p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-success" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-muted">No payments recorded yet.</p>
            @endforelse
        </div>

        <p class="text-center text-xs text-muted">Confidential — this section is read-only.</p>
    </div>
</x-app-layout>
