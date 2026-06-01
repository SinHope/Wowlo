<x-app-layout>
    <x-slot name="header">WhatsApp Billing</x-slot>

    @php $cur = config('wowlo.currency'); @endphp

    <div class="mx-auto max-w-5xl space-y-5">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">WhatsApp Billing</h2>
                <p class="text-muted">{{ $bills->total() }} {{ Str::plural('bill', $bills->total()) }} generated</p>
            </div>
            <a href="{{ route('tutor.billing.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Generate Bill
            </a>
        </div>

        @forelse ($bills as $bill)
            <a href="{{ route('tutor.billing.show', $bill) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-ink">{{ $bill->student->name }}</p>
                    <p class="mt-0.5 text-sm text-muted">{{ $bill->billing_month->format('F Y') }} · billed {{ $cur }} {{ number_format($bill->charges_total, 2) }}</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-xs text-muted">Grand total</p>
                    <p class="font-extrabold text-primary-dark">{{ $cur }} {{ number_format($bill->grand_total, 2) }}</p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No bills yet</p>
                <p class="mt-1 text-sm text-muted">Generate your first WhatsApp bill.</p>
            </div>
        @endforelse

        @if ($bills->hasPages())
            <div>{{ $bills->links() }}</div>
        @endif
    </div>
</x-app-layout>
