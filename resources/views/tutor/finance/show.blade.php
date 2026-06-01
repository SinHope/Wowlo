<x-app-layout>
    <x-slot name="header">Finance · {{ $student->name }}</x-slot>

    @php $cur = config('wowlo.currency'); @endphp

    <div class="mx-auto max-w-4xl space-y-5">

        <div class="flex items-center gap-3">
            <a href="{{ route('tutor.finance.index') }}" class="text-muted hover:text-ink" aria-label="Back">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </a>
            <h2 class="text-2xl font-extrabold text-ink">{{ $student->name }}</h2>
        </div>

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <!-- Summary cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-muted">Total billed</p>
                <p class="mt-1 text-xl font-extrabold text-ink">{{ $cur }} {{ number_format($totalBilled, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-muted">Total paid</p>
                <p class="mt-1 text-xl font-extrabold text-ink">{{ $cur }} {{ number_format($totalPaid, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-muted">Outstanding</p>
                <p @class([
                    'mt-1 text-xl font-extrabold',
                    'text-danger' => $outstanding > 0.001,
                    'text-success' => $outstanding < -0.001,
                    'text-ink' => abs($outstanding) <= 0.001,
                ])>
                    @if ($outstanding < -0.001)
                        {{ $cur }} {{ number_format(abs($outstanding), 2) }} credit
                    @else
                        {{ $cur }} {{ number_format($outstanding, 2) }}
                    @endif
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <!-- Fee rate -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-ink">Hourly Rate</h3>
                <p class="text-sm text-muted">Used to itemise lessons on the billing page.</p>

                <form method="POST" action="{{ route('tutor.finance.fee.save', $student) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="fee_rate_per_hour" :value="'Rate per hour (' . $cur . ')'" />
                        <x-text-input id="fee_rate_per_hour" name="fee_rate_per_hour" type="number" step="0.01" min="0"
                                      class="mt-1 block w-full"
                                      :value="old('fee_rate_per_hour', $fee?->fee_rate_per_hour)"
                                      placeholder="e.g. 50.00" required />
                        <x-input-error :messages="$errors->get('fee_rate_per_hour')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="remarks" value="Remarks (optional)" />
                        <textarea id="remarks" name="remarks" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">{{ old('remarks', $fee?->remarks) }}</textarea>
                        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                    </div>

                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        Save rate
                    </button>
                </form>
            </div>

            <!-- Record payment -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-ink">Record a Payment</h3>
                <p class="text-sm text-muted">Lowers the outstanding balance.</p>

                <form method="POST" action="{{ route('tutor.finance.payments.store', $student) }}" class="mt-4 space-y-4">
                    @csrf

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="amount_paid" :value="'Amount (' . $cur . ')'" />
                            <x-text-input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0.01"
                                          class="mt-1 block w-full" :value="old('amount_paid')" required />
                            <x-input-error :messages="$errors->get('amount_paid')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="payment_date" value="Date" />
                            <x-text-input id="payment_date" name="payment_date" type="date"
                                          class="mt-1 block w-full" :value="old('payment_date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="payment_method" value="Method" />
                        <select id="payment_method" name="payment_method" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                            @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'paynow' => 'PayNow', 'paypal' => 'PayPal'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('payment_method') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="payment_remarks" value="Remarks (optional)" />
                        <x-text-input id="payment_remarks" name="remarks" type="text"
                                      class="mt-1 block w-full" :value="old('remarks')" />
                        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                    </div>

                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        Record payment
                    </button>
                </form>
            </div>
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
                    <form method="POST" action="{{ route('tutor.finance.payments.destroy', $payment) }}"
                          onsubmit="return confirm('Delete this payment?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-danger hover:underline cursor-pointer">Delete</button>
                    </form>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-muted">No payments recorded yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
