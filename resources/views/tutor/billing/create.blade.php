<x-app-layout>
    <x-slot name="header">WhatsApp Billing</x-slot>

    <div class="mx-auto max-w-4xl space-y-5"
         x-data="billingForm({
            students: {{ Illuminate\Support\Js::from($students) }},
            currency: @js($currency),
            paymentInstructions: @js($paymentInstructions),
            defaultMonth: @js(now()->format('Y-m')),
         })">

        <div class="flex items-center gap-3">
            <a href="{{ route('tutor.billing.index') }}" class="text-muted hover:text-ink" aria-label="Back">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </a>
            <h2 class="text-2xl font-extrabold text-ink">Generate a Bill</h2>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3">
                <p class="text-sm font-semibold text-danger">Please fix the following:</p>
                <ul class="mt-1 list-disc list-inside text-sm text-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tutor.billing.store') }}" class="space-y-5">
            @csrf

            <!-- Student + month -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="student_id" value="Student" />
                        <select id="student_id" name="student_id" x-model="studentId" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">Select a student…</option>
                            <template x-for="s in students" :key="s.id">
                                <option :value="s.id" x-text="s.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="billing_month" value="Billing month" />
                        <input id="billing_month" name="billing_month" type="month" x-model="billingMonth" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    </div>
                </div>

                <!-- Rate / outstanding readout -->
                <div x-show="student" x-cloak class="mt-4 flex flex-wrap gap-4 text-sm">
                    <p class="text-muted">Rate: <span class="font-bold text-ink" x-text="rate !== null ? money(rate) + '/hr' : '—'"></span></p>
                    <p class="text-muted">Current outstanding: <span class="font-bold text-ink" x-text="money(outstanding)"></span></p>
                </div>

                <div x-show="student && rate === null" x-cloak class="mt-3 rounded-lg border border-amber/40 bg-amber/10 px-3 py-2 text-sm font-semibold text-amber-700">
                    This student has no hourly rate set. Set it in Finance before billing.
                </div>
            </div>

            <!-- Lessons -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-ink">Lessons</h3>
                    <button type="button" @click="addLesson()"
                            class="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-semibold text-primary-dark hover:bg-primary/20 cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add lesson
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <template x-for="(lesson, i) in lessons" :key="i">
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="text-xs font-semibold text-muted" x-show="i === 0">Date</label>
                                <input type="date" :name="`lessons[${i}][lesson_date]`" x-model="lesson.lesson_date" required
                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                            </div>
                            <div class="w-24">
                                <label class="text-xs font-semibold text-muted" x-show="i === 0">Hours</label>
                                <input type="number" step="0.25" min="0.25" :name="`lessons[${i}][hours]`" x-model="lesson.hours" placeholder="1.5" required
                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                            </div>
                            <div class="w-28 text-right">
                                <label class="text-xs font-semibold text-muted" x-show="i === 0">Amount</label>
                                <p class="mt-1 py-2 text-sm font-bold text-ink" x-text="money(lineAmount(lesson))"></p>
                            </div>
                            <button type="button" @click="removeLesson(i)" x-show="lessons.length > 1"
                                    class="mb-1 text-danger hover:text-danger/70 cursor-pointer" aria-label="Remove">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex justify-end border-t border-gray-100 pt-3 text-sm">
                    <span class="text-muted">Lessons subtotal:&nbsp;</span>
                    <span class="font-bold text-ink" x-text="money(lessonsSubtotal)"></span>
                </div>
            </div>

            <!-- Additional charges -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-ink">Additional Charges <span class="text-sm font-normal text-muted">(optional)</span></h3>
                    <button type="button" @click="addCharge()"
                            class="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-semibold text-primary-dark hover:bg-primary/20 cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add charge
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <template x-for="(charge, i) in charges" :key="i">
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="text-xs font-semibold text-muted" x-show="i === 0">Description</label>
                                <input type="text" :name="`charges[${i}][description]`" x-model="charge.description" placeholder="e.g. Assessment book"
                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                            </div>
                            <div class="w-28">
                                <label class="text-xs font-semibold text-muted" x-show="i === 0">Amount</label>
                                <input type="number" step="0.01" min="0" :name="`charges[${i}][amount]`" x-model="charge.amount" placeholder="12.50"
                                       class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                            </div>
                            <button type="button" @click="removeCharge(i)"
                                    class="mb-1 text-danger hover:text-danger/70 cursor-pointer" aria-label="Remove">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </div>
                    </template>
                    <p x-show="charges.length === 0" class="text-sm text-muted">No additional charges.</p>
                </div>
            </div>

            <!-- Totals + preview -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-muted">Lessons subtotal</span><span class="font-semibold text-ink" x-text="money(lessonsSubtotal)"></span></div>
                    <div class="flex justify-between" x-show="additionalTotal > 0"><span class="text-muted">Additional charges</span><span class="font-semibold text-ink" x-text="money(additionalTotal)"></span></div>
                    <div class="flex justify-between" x-show="Math.abs(outstanding) > 0.001"><span class="text-muted">Outstanding (carried over)</span><span class="font-semibold text-ink" x-text="money(outstanding)"></span></div>
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base"><span class="font-bold text-ink">Grand total</span><span class="font-extrabold text-primary-dark" x-text="money(grandTotal)"></span></div>
                </div>

                <!-- Live WhatsApp preview -->
                <div class="mt-5">
                    <p class="mb-1 text-xs font-semibold text-muted">WhatsApp message preview</p>
                    <pre class="max-h-64 overflow-auto whitespace-pre-wrap rounded-xl bg-cream p-4 font-sans text-sm text-ink" x-text="message"></pre>
                </div>

                <div class="mt-5 flex items-center justify-end gap-3">
                    <button type="submit" :disabled="!canSave"
                            :class="canSave ? 'bg-primary hover:bg-primary-dark cursor-pointer' : 'bg-gray-300 cursor-not-allowed'"
                            class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Save &amp; generate
                    </button>
                </div>
            </div>
        </form>
    </div>

    <style>[x-cloak]{display:none!important;}</style>

    <script>
        function billingForm(config) {
            return {
                students: config.students,
                currency: config.currency || 'SGD',
                paymentInstructions: config.paymentInstructions,
                studentId: '',
                billingMonth: config.defaultMonth,
                lessons: [{ lesson_date: '', hours: '' }],
                charges: [],

                get student() {
                    return this.students.find(s => String(s.id) === String(this.studentId)) || null;
                },
                get rate() { return this.student ? this.student.rate : null; },
                get outstanding() { return this.student ? Number(this.student.outstanding) : 0; },

                round2(n) { return Math.round((Number(n) + Number.EPSILON) * 100) / 100; },
                money(n) { return this.currency + ' ' + this.round2(n || 0).toFixed(2); },

                lineAmount(lesson) {
                    if (this.rate === null) return 0;
                    return this.round2(this.rate * parseFloat(lesson.hours || 0));
                },
                get lessonsSubtotal() {
                    return this.round2(this.lessons.reduce((sum, l) => sum + this.lineAmount(l), 0));
                },
                get additionalTotal() {
                    return this.round2(this.charges.reduce((sum, c) => sum + parseFloat(c.amount || 0), 0));
                },
                get grandTotal() {
                    return this.round2(this.lessonsSubtotal + this.additionalTotal + this.outstanding);
                },
                get canSave() {
                    return this.student !== null && this.rate !== null && this.lessons.some(l => l.lesson_date && l.hours);
                },

                addLesson() { this.lessons.push({ lesson_date: '', hours: '' }); },
                removeLesson(i) { this.lessons.splice(i, 1); },
                addCharge() { this.charges.push({ description: '', amount: '' }); },
                removeCharge(i) { this.charges.splice(i, 1); },

                fmtDate(d) {
                    if (!d) return '';
                    const [y, m, day] = d.split('-');
                    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    return `${day} ${months[parseInt(m, 10) - 1]}`;
                },
                fmtHours(h) { return String(parseFloat(h || 0)).replace(/\.00$/, ''); },

                get message() {
                    const name = this.student ? this.student.name : '______';
                    let monthLabel = '';
                    if (this.billingMonth) {
                        const [y, m] = this.billingMonth.split('-');
                        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                        monthLabel = `${months[parseInt(m, 10) - 1]} ${y}`;
                    }

                    const lines = [];
                    lines.push(`Hi! Here is the tuition fee for ${name} — ${monthLabel}.`);
                    lines.push('');
                    lines.push('Lessons:');
                    this.lessons.forEach(l => {
                        if (!l.lesson_date && !l.hours) return;
                        lines.push(`- ${this.fmtDate(l.lesson_date)}: ${this.fmtHours(l.hours)}h × ${this.money(this.rate || 0)} = ${this.money(this.lineAmount(l))}`);
                    });
                    lines.push(`Lessons subtotal: ${this.money(this.lessonsSubtotal)}`);

                    const realCharges = this.charges.filter(c => c.description || c.amount);
                    if (realCharges.length) {
                        lines.push('');
                        lines.push('Additional charges:');
                        realCharges.forEach(c => lines.push(`- ${c.description || '(item)'}: ${this.money(c.amount)}`));
                    }

                    if (Math.abs(this.outstanding) > 0.001) {
                        lines.push('');
                        lines.push(`Outstanding balance (carried over): ${this.money(this.outstanding)}`);
                    }

                    lines.push('');
                    lines.push(`*Grand total due: ${this.money(this.grandTotal)}*`);

                    const payment = (this.paymentInstructions || '').trim();
                    if (payment) {
                        lines.push('');
                        lines.push(payment);
                    }
                    lines.push('Thank you!');

                    return lines.join('\n');
                },
            };
        }
    </script>
</x-app-layout>
