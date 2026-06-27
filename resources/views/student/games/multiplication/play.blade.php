<x-app-layout>
    <x-slot name="header">Multiplication Rabbit</x-slot>

    @php
        // Mascot: the rabbit image if it's been added, else a teal icon badge.
        $mascot = function (string $sizeClasses, bool $spin = false) use ($rabbitImage) {
            if ($rabbitImage) {
                return '<img src="' . e($rabbitImage) . '" alt="Multiplication Rabbit mascot" class="' . $sizeClasses
                    . ($spin ? ' animate-spin motion-reduce:animate-none' : '') . ' drop-shadow-lg"'
                    . ($spin ? ' style="animation-duration: 1.6s"' : '') . '>';
            }
            return '<span class="inline-flex items-center justify-center rounded-full bg-teal-500/15 text-teal-600 ' . $sizeClasses
                . ($spin ? ' animate-spin motion-reduce:animate-none' : '') . '"'
                . ($spin ? ' style="animation-duration: 1.6s"' : '') . '>'
                . '<svg class="h-1/2 w-1/2" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">'
                . '<path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />'
                . '</svg></span>';
        };
    @endphp

    <div
        x-data="multiplicationGame()"
        x-init="boot()"
        @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
        class="mx-auto max-w-3xl"
    >
        {{-- ============================ LOADING ============================ --}}
        <div x-show="screen === 'loading'" x-cloak
             class="flex flex-col items-center justify-center text-center" style="min-height: calc(100vh - 9rem)">
            {!! $mascot('h-32 w-32', true) !!}
            <p class="mt-6 text-xl font-extrabold tracking-wide text-teal-700">
                Firing Up<span x-text="dots"></span>
            </p>
        </div>

        {{-- ========================== LEVEL SELECT ========================= --}}
        <div x-show="screen === 'level'" x-cloak>
            @include('student.games.multiplication.partials.tabs', ['active' => 'play'])

            <div class="flex flex-col items-center justify-center text-center" style="min-height: calc(100vh - 14rem)">
                {!! $mascot('h-20 w-20') !!}
                <h2 class="mt-4 text-2xl font-extrabold text-ink">Select Your Level</h2>
                <p class="mt-1 text-sm text-gray-500">Pick how big the numbers should be, then hop to it!</p>

                <div class="mt-8 w-full max-w-md">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($levels as $lvl)
                            <button type="button" @click="pickLevel(@js($lvl['label']))"
                                    class="rounded-2xl border-2 border-teal-500/20 bg-white px-3 py-4 text-base font-extrabold text-teal-700 shadow-sm transition-colors duration-200 hover:border-teal-500 hover:bg-teal-500/5 cursor-pointer">
                                {{ $lvl['label'] }}
                            </button>
                        @endforeach

                        @if ($mixedEnabled)
                            <button type="button" @click="pickLevel(@js($mixedLevel))"
                                    class="col-span-2 rounded-2xl border-2 border-teal-500/20 bg-teal-500/5 px-3 py-4 text-sm font-extrabold text-teal-700 shadow-sm transition-colors duration-200 hover:border-teal-500 hover:bg-teal-500/10 cursor-pointer">
                                {{ $mixedLevel }}
                                <span class="mt-0.5 block text-[11px] font-semibold text-gray-500">A mix of all the levels above</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================= PLAY ============================= --}}
        <div x-show="screen === 'play'" x-cloak>
            {{-- siren — last 15 seconds of a timed round --}}
            <div x-show="sirenOn" x-cloak class="mb-3 flex justify-center">
                <x-siren-lights />
            </div>

            {{-- Soft background card --}}
            <div class="relative overflow-hidden rounded-3xl border border-gray-100 shadow-sm"
                 :style="bgStyle"
                 :class="bg ? '' : 'bg-gradient-to-br from-teal-500/10 via-cream to-emerald-500/10'">
                {{-- readability scrim over the photo --}}
                <div class="absolute inset-0 bg-white/55"></div>

                <div class="relative px-5 py-8 sm:px-10 sm:py-12">
                    {{-- progress + timer --}}
                    <div class="mb-6 flex items-center justify-between text-sm font-bold text-teal-700">
                        <span x-text="chosenLevel"></span>
                        <div class="flex items-center gap-3">
                            <template x-if="timed">
                                <span class="rounded-full px-2 py-0.5 font-extrabold tabular-nums"
                                      :class="remaining <= 15 ? 'bg-danger text-white' : 'bg-white/70 text-ink'"
                                      x-text="formatTime(remaining)"></span>
                            </template>
                            <span>Question <span x-text="current + 1"></span> / <span x-text="questions.length"></span></span>
                        </div>
                    </div>
                    <div class="mb-8 h-2 w-full overflow-hidden rounded-full bg-white/70">
                        <div class="h-full rounded-full bg-teal-500 transition-all duration-300"
                             :style="`width: ${((current + 1) / questions.length) * 100}%`"></div>
                    </div>

                    <p class="text-center text-sm font-semibold uppercase tracking-wide text-gray-500">What's the answer?</p>

                    {{-- the question --}}
                    <p class="mt-2 text-center text-5xl font-extrabold text-ink sm:text-6xl">
                        <span x-text="questions[current]?.a"></span>
                        <span class="text-teal-500"> &times; </span>
                        <span x-text="questions[current]?.b"></span>
                    </p>

                    <p class="mt-6 text-center text-sm font-semibold text-gray-600">Type your answer:</p>

                    {{-- answer input --}}
                    <div class="mt-4 flex justify-center" x-ref="answerWrap">
                        <input type="number" inputmode="numeric" autocomplete="off" step="1" min="0"
                               aria-label="answer"
                               x-model="responses[current]"
                               @keydown="onKey($event)"
                               class="h-16 w-48 rounded-2xl border-2 border-teal-500/30 bg-white/90 text-center text-3xl font-extrabold text-ink shadow-sm transition-colors duration-150 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                    </div>

                    {{-- action --}}
                    <div class="mt-8 flex justify-center">
                        <button type="button" @click="advance()"
                                class="rounded-2xl bg-teal-500 px-8 py-3.5 text-lg font-extrabold text-white shadow-sm transition-colors duration-200 hover:bg-teal-600 cursor-pointer"
                                x-text="current === questions.length - 1 ? 'Done' : 'Hop!'"></button>
                    </div>
                    <p class="mt-3 text-center text-xs text-gray-500">Tip: press <kbd class="rounded bg-white/80 px-1.5 py-0.5 font-semibold">Enter</kbd> to continue</p>
                </div>
            </div>
        </div>

        {{-- ==================== QUESTION-COUNT MODAL ===================== --}}
        <div x-show="countPrompt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="cancelChoice()"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
                <h3 class="text-lg font-extrabold text-ink">How many questions?</h3>
                <p class="mt-2 text-sm text-gray-600" x-text="pendingLevel"></p>
                <div class="mt-6 grid grid-cols-3 gap-2">
                    <template x-for="n in questionCounts" :key="n">
                        <button type="button" @click="chooseCount(n)"
                                class="rounded-xl border-2 border-teal-500/20 px-3 py-4 text-base font-extrabold text-teal-700 transition-colors duration-200 hover:border-teal-500 hover:bg-teal-500/5 cursor-pointer">
                            <span x-text="n"></span>
                        </button>
                    </template>
                </div>
                <button type="button" @click="cancelChoice()" class="mt-5 text-sm font-semibold text-gray-500 hover:text-teal-700 cursor-pointer">Cancel</button>
            </div>
        </div>

        {{-- ===================== TIMER CHOICE MODAL ====================== --}}
        <div x-show="timerPrompt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="cancelChoice()"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
                <h3 class="text-lg font-extrabold text-ink">How do you want to play?</h3>
                <p class="mt-2 text-sm text-gray-600"><span x-text="pendingLevel"></span> · <span x-text="pendingCount"></span> questions</p>
                <div class="mt-6 flex flex-col gap-2">
                    <button type="button" @click="chooseInfinite()"
                            class="rounded-xl border-2 border-teal-500 px-5 py-3 text-base font-extrabold text-teal-700 transition-colors duration-200 hover:bg-teal-500/5 cursor-pointer">
                        Infinite time
                    </button>
                    <button type="button" @click="chooseTimed()"
                            class="rounded-xl bg-teal-500 px-5 py-3 text-base font-extrabold text-white transition-colors duration-200 hover:bg-teal-600 cursor-pointer">
                        Set a timer
                    </button>
                </div>
                <button type="button" @click="backToCount()" class="mt-4 text-sm font-semibold text-gray-500 hover:text-teal-700 cursor-pointer">&larr; Back</button>
            </div>
        </div>

        {{-- ==================== DURATION CHOICE MODAL ==================== --}}
        <div x-show="durationPrompt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="cancelChoice()"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
                <h3 class="text-lg font-extrabold text-ink">How much time?</h3>
                <div class="mt-5 grid grid-cols-3 gap-2">
                    <template x-for="m in [1, 3, 5, 7, 10]" :key="m">
                        <button type="button" @click="setDuration(m)"
                                class="rounded-xl border-2 border-teal-500/20 px-3 py-3 text-base font-extrabold text-teal-700 transition-colors duration-200 hover:border-teal-500 hover:bg-teal-500/5 cursor-pointer">
                            <span x-text="m"></span> min
                        </button>
                    </template>
                </div>

                {{-- other time --}}
                <div class="mt-4">
                    <label class="block text-left text-xs font-semibold text-gray-500">Other time (minutes)</label>
                    <div class="mt-1 flex gap-2">
                        <input type="number" min="1" max="180" x-model="customMinutes"
                               @keydown.enter.prevent="confirmCustom()"
                               placeholder="e.g. 15"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                        <button type="button" @click="confirmCustom()"
                                class="shrink-0 rounded-xl bg-teal-500 px-4 py-2 text-sm font-extrabold text-white transition-colors duration-200 hover:bg-teal-600 cursor-pointer">
                            Go
                        </button>
                    </div>
                    <p x-show="customError" x-cloak class="mt-1 text-left text-xs text-danger" x-text="customError"></p>
                </div>

                <button type="button" @click="durationPrompt = false; timerPrompt = true"
                        class="mt-5 text-sm font-semibold text-gray-500 hover:text-teal-700 cursor-pointer">
                    &larr; Back
                </button>
            </div>
        </div>

        {{-- ====================== REVIEW-AGAIN MODAL ====================== --}}
        <div x-show="reviewPrompt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="reviewPrompt = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
                <h3 class="text-lg font-extrabold text-ink">Look through your answers again?</h3>
                <p class="mt-2 text-sm text-gray-600">You can revisit each question and change your answer before you finish.</p>
                <div class="mt-6 flex flex-col gap-2">
                    <button type="button" @click="reviewAgain()"
                            class="rounded-xl border-2 border-teal-500 px-5 py-3 text-base font-extrabold text-teal-700 transition-colors duration-200 hover:bg-teal-500/5 cursor-pointer">
                        Yes, let me check
                    </button>
                    <button type="button" @click="submit()"
                            class="rounded-xl bg-teal-500 px-5 py-3 text-base font-extrabold text-white transition-colors duration-200 hover:bg-teal-600 cursor-pointer">
                        No, I'm done
                    </button>
                </div>
            </div>
        </div>

        {{-- hidden POST form, submitted programmatically --}}
        <form method="POST" action="{{ route('student.games.multiplication.finish') }}" x-ref="finishForm" class="hidden">
            @csrf
            <input type="hidden" name="level" :value="chosenLevel">
            <template x-for="(q, i) in questions" :key="'f-' + i">
                <span>
                    <input type="hidden" :name="`items[${i}][a]`" :value="q.a">
                    <input type="hidden" :name="`items[${i}][b]`" :value="q.b">
                    <input type="hidden" :name="`items[${i}][response]`" :value="responses[i]">
                </span>
            </template>
        </form>
    </div>

    <script>
        function multiplicationGame() {
            return {
                // server data
                rangesByLevel: @js($rangesByLevel),
                realLevels:    @js($realLevels),
                mixedLevel:    @js($mixedLevel),
                questionCounts: @js($questionCounts),
                defaultCount:  @js($defaultCount),
                backgrounds:   @js($backgrounds),

                // ui state
                screen: 'loading',     // loading | level | play
                dots: '',
                reviewPrompt: false,

                // choice flow
                countPrompt: false,
                timerPrompt: false,
                durationPrompt: false,
                pendingLevel: null,
                pendingCount: null,
                customMinutes: '',
                customError: '',

                // round state
                chosenLevel: null,
                questions: [],
                responses: [],
                current: 0,
                bg: null,

                // timer runtime
                timed: false,
                remaining: null,
                sirenOn: false,
                _timer: null,
                _submitting: false,
                _audioCtx: null,

                boot() {
                    let n = 0;
                    this._dotTimer = setInterval(() => { n = (n + 1) % 4; this.dots = '.'.repeat(n); }, 350);
                    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    setTimeout(() => { this.screen = 'level'; clearInterval(this._dotTimer); }, reduce ? 400 : 1800);
                },

                // level → how many questions → timer → play
                pickLevel(label) { this.pendingLevel = label; this.countPrompt = true; },
                chooseCount(n) { this.pendingCount = n; this.countPrompt = false; this.timerPrompt = true; },
                backToCount() { this.timerPrompt = false; this.countPrompt = true; },
                cancelChoice() {
                    this.countPrompt = this.timerPrompt = this.durationPrompt = false;
                    this.pendingLevel = this.pendingCount = null;
                },
                chooseInfinite() { this.timerPrompt = false; this.startRound(null); },
                chooseTimed() { this.timerPrompt = false; this.durationPrompt = true; },
                setDuration(mins) { this.durationPrompt = false; this.startRound(mins); },
                confirmCustom() {
                    const m = parseInt(this.customMinutes, 10);
                    if (!Number.isInteger(m) || m < 1 || m > 180) {
                        this.customError = 'Enter a whole number of minutes (1–180).';
                        return;
                    }
                    this.customError = '';
                    this.setDuration(m);
                },

                randInt(lo, hi) { return Math.floor(Math.random() * (hi - lo + 1)) + lo; },

                // One question for a level. Mixed → pick a random real level first.
                // The server re-validates every factor against the same ranges.
                makeQuestion(label) {
                    let lvl = label;
                    if (label === this.mixedLevel) {
                        lvl = this.realLevels[Math.floor(Math.random() * this.realLevels.length)];
                    }
                    const r = this.rangesByLevel[lvl];
                    return { a: this.randInt(r.a[0], r.a[1]), b: this.randInt(r.b[0], r.b[1]) };
                },

                startRound(minutes) {
                    const count = this.questionCounts.includes(this.pendingCount) ? this.pendingCount : this.defaultCount;
                    this.chosenLevel = this.pendingLevel;
                    this.questions = Array.from({ length: count }, () => this.makeQuestion(this.chosenLevel));
                    this.responses = this.questions.map(() => '');
                    this.current = 0;
                    this.bg = this.backgrounds.length ? this.backgrounds[Math.floor(Math.random() * this.backgrounds.length)] : null;

                    // timer
                    this.clearTimer();
                    this.sirenOn = false;
                    this.timed = minutes !== null && minutes > 0;
                    this.remaining = this.timed ? minutes * 60 : null;
                    if (this.timed) {
                        this._timer = setInterval(() => this.tick(), 1000);
                    }

                    this.screen = 'play';
                    this.$nextTick(() => this.focusAnswer());
                },

                tick() {
                    this.remaining--;
                    if (this.remaining <= 15) this.sirenOn = true;
                    if (this.remaining <= 0) {
                        this.remaining = 0;
                        this.clearTimer();
                        this.submit(); // time's up — auto-complete + mark
                    }
                },

                clearTimer() { if (this._timer) { clearInterval(this._timer); this._timer = null; } },

                formatTime(s) {
                    s = Math.max(0, s | 0);
                    const m = Math.floor(s / 60);
                    return m + ':' + String(s % 60).padStart(2, '0');
                },

                get bgStyle() {
                    return this.bg ? `background-image:url('${this.bg}');background-size:cover;background-position:center;` : '';
                },

                focusAnswer() {
                    const input = this.$refs.answerWrap?.querySelector('input');
                    if (input) input.focus();
                },

                onKey(e) {
                    if (e.key === 'Enter') { e.preventDefault(); this.advance(); }
                },

                advance() {
                    this.playHop();
                    if (this.current < this.questions.length - 1) {
                        this.current++;
                        this.$nextTick(() => this.focusAnswer());
                    } else {
                        this.reviewPrompt = true;
                    }
                },

                // Short synthesized "hop" — a quick downward pitch bounce. No audio file.
                playHop() {
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                    try {
                        const Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) return;
                        this._audioCtx = this._audioCtx || new Ctx();
                        const ctx = this._audioCtx;
                        if (ctx.state === 'suspended') ctx.resume();
                        const now = ctx.currentTime;
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(520, now);
                        osc.frequency.exponentialRampToValueAtTime(220, now + 0.13);
                        gain.gain.setValueAtTime(0.0001, now);
                        gain.gain.exponentialRampToValueAtTime(0.2, now + 0.01);
                        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.18);
                        osc.connect(gain).connect(ctx.destination);
                        osc.start(now);
                        osc.stop(now + 0.2);
                    } catch (_) { /* audio unsupported — fail silently */ }
                },

                reviewAgain() {
                    this.reviewPrompt = false;
                    this.current = 0;
                    this.$nextTick(() => this.focusAnswer());
                },

                submit() {
                    if (this._submitting) return;
                    this._submitting = true;
                    this.clearTimer();
                    this.reviewPrompt = false;
                    this.$refs.finishForm.submit();
                },
            };
        }
    </script>
</x-app-layout>
