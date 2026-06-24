<x-app-layout>
    <x-slot name="header">Spelling Meow</x-slot>

    @php
        $primary   = $levels->where('group', 'Primary')->values();
        $secondary = $levels->where('group', 'Secondary')->values();
    @endphp

    <div
        x-data="spellingGame()"
        x-init="boot()"
        @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
        class="mx-auto max-w-3xl"
    >
        {{-- ============================ LOADING ============================ --}}
        <div x-show="screen === 'loading'" x-cloak
             class="flex flex-col items-center justify-center text-center" style="min-height: calc(100vh - 9rem)">
            <img src="{{ $catImage }}" alt="Spelling Meow mascot"
                 class="h-32 w-32 animate-spin motion-reduce:animate-none drop-shadow-lg"
                 style="animation-duration: 1.6s">
            <p class="mt-6 text-xl font-extrabold tracking-wide text-primary-dark">
                Firing Up<span x-text="dots"></span>
            </p>
        </div>

        {{-- ========================== LEVEL SELECT ========================= --}}
        <div x-show="screen === 'level'" x-cloak>
            @include('student.games.spelling.partials.tabs', ['active' => 'play'])

            <div class="flex flex-col items-center justify-center text-center" style="min-height: calc(100vh - 14rem)">
                <img src="{{ $catImage }}" alt="" class="h-20 w-20 drop-shadow">
                <h2 class="mt-4 text-2xl font-extrabold text-ink">Select Your Level</h2>
                <p class="mt-1 text-sm text-gray-500">Pick where you are and let's spell!</p>

                {{-- Step 1: which school --}}
                <div x-show="stage === 'school'" class="mt-8 flex w-full max-w-xs flex-col gap-3">
                    <button type="button" @click="chooseSchool('Primary')"
                            class="rounded-2xl bg-primary px-6 py-4 text-lg font-extrabold text-white shadow-sm transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        Primary School
                    </button>
                    <button type="button" @click="chooseSchool('Secondary')"
                            class="rounded-2xl bg-primary px-6 py-4 text-lg font-extrabold text-white shadow-sm transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        Secondary School
                    </button>
                </div>

                {{-- Step 2: which level --}}
                <div x-show="stage === 'levels'" x-cloak class="mt-8 w-full max-w-md">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {{-- Primary --}}
                        <template x-if="school === 'Primary'">
                            <div class="contents">
                                @foreach ($primary as $lvl)
                                    @php $isMixed = $lvl['label'] === $mixedLevel; @endphp
                                    @if ($lvl['enabled'])
                                        <button type="button" @click="pickLevel(@js($lvl['label']), 'Primary')"
                                                @class([
                                                    'rounded-2xl border-2 border-primary/20 bg-white px-3 py-4 font-extrabold text-primary-dark shadow-sm transition-colors duration-200 hover:border-primary hover:bg-primary/5 cursor-pointer',
                                                    'col-span-2 sm:col-span-3 text-sm bg-primary/5' => $isMixed,
                                                    'text-base' => ! $isMixed,
                                                ])>
                                            {{ $lvl['label'] }}
                                            @if ($isMixed)<span class="mt-0.5 block text-[11px] font-semibold text-gray-500">30 random words from P1–P6</span>@endif
                                        </button>
                                    @else
                                        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 px-3 py-3 text-center">
                                            <span class="text-base font-bold text-gray-400 line-through">{{ $lvl['label'] }}</span>
                                            <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-accent-dark">Building in progress..</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </template>
                        {{-- Secondary --}}
                        <template x-if="school === 'Secondary'">
                            <div class="contents">
                                @foreach ($secondary as $lvl)
                                    @if ($lvl['enabled'])
                                        <button type="button" @click="pickLevel(@js($lvl['label']), 'Secondary')"
                                                class="rounded-2xl border-2 border-primary/20 bg-white px-3 py-4 text-base font-extrabold text-primary-dark shadow-sm transition-colors duration-200 hover:border-primary hover:bg-primary/5 cursor-pointer">
                                            {{ $lvl['label'] }}
                                        </button>
                                    @else
                                        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 px-3 py-3 text-center">
                                            <span class="text-base font-bold text-gray-400 line-through">{{ $lvl['label'] }}</span>
                                            <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-accent-dark">Building in progress..</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="stage = 'school'"
                            class="mt-6 text-sm font-semibold text-gray-500 hover:text-primary-dark cursor-pointer">
                        &larr; Back
                    </button>
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
                 :class="bg ? '' : 'bg-gradient-to-br from-primary/10 via-cream to-accent/10'">
                {{-- readability scrim over the photo --}}
                <div class="absolute inset-0 bg-white/55"></div>

                <div class="relative px-5 py-8 sm:px-10 sm:py-12">
                    {{-- progress + timer --}}
                    <div class="mb-6 flex items-center justify-between text-sm font-bold text-primary-dark">
                        <span x-text="chosenLevel"></span>
                        <div class="flex items-center gap-3">
                            <template x-if="timed">
                                <span class="rounded-full px-2 py-0.5 font-extrabold tabular-nums"
                                      :class="remaining <= 15 ? 'bg-danger text-white' : 'bg-white/70 text-ink'"
                                      x-text="formatTime(remaining)"></span>
                            </template>
                            <span>Word <span x-text="current + 1"></span> / <span x-text="words.length"></span></span>
                        </div>
                    </div>
                    <div class="mb-8 h-2 w-full overflow-hidden rounded-full bg-white/70">
                        <div class="h-full rounded-full bg-primary transition-all duration-300"
                             :style="`width: ${((current + 1) / words.length) * 100}%`"></div>
                    </div>

                    <p class="text-center text-sm font-semibold uppercase tracking-wide text-gray-500">Spot &amp; fix the spelling</p>

                    {{-- the misspelled prompt --}}
                    <p class="mt-2 text-center text-4xl font-extrabold text-danger line-through decoration-2 sm:text-5xl"
                       x-text="words[current]?.shown"></p>

                    <p class="mt-6 text-center text-sm font-semibold text-gray-600">Type the correct spelling:</p>

                    {{-- letter blanks --}}
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2" x-ref="boxes">
                        <template x-for="(ch, j) in letters[current]" :key="current + '-' + j">
                            <input type="text" maxlength="1" autocomplete="off" autocapitalize="characters"
                                   spellcheck="false" aria-label="letter"
                                   x-model="letters[current][j]"
                                   @input="onInput($event)"
                                   @keydown="onKey($event, j)"
                                   class="h-14 w-11 rounded-xl border-2 border-primary/30 bg-white/90 text-center text-2xl font-extrabold uppercase text-ink shadow-sm transition-colors duration-150 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40 sm:h-16 sm:w-12">
                        </template>
                    </div>

                    {{-- action --}}
                    <div class="mt-8 flex justify-center">
                        <button type="button" @click="advance()"
                                class="rounded-2xl bg-accent px-8 py-3.5 text-lg font-extrabold text-white shadow-sm transition-colors duration-200 hover:brightness-95 cursor-pointer"
                                x-text="current === words.length - 1 ? 'Done With Spelling' : 'Spelt'"></button>
                    </div>
                    <p class="mt-3 text-center text-xs text-gray-500">Tip: press <kbd class="rounded bg-white/80 px-1.5 py-0.5 font-semibold">Enter</kbd> to continue</p>
                </div>
            </div>
        </div>

        {{-- ===================== TIMER CHOICE MODAL ====================== --}}
        <div x-show="timerPrompt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="cancelTimer()"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
                <h3 class="text-lg font-extrabold text-ink">How do you want to play?</h3>
                <p class="mt-2 text-sm text-gray-600" x-text="pendingLevel"></p>
                <div class="mt-6 flex flex-col gap-2">
                    <button type="button" @click="chooseInfinite()"
                            class="rounded-xl border-2 border-primary px-5 py-3 text-base font-extrabold text-primary-dark transition-colors duration-200 hover:bg-primary/5 cursor-pointer">
                        Infinite time
                    </button>
                    <button type="button" @click="chooseTimed()"
                            class="rounded-xl bg-primary px-5 py-3 text-base font-extrabold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        Set a timer
                    </button>
                </div>
                <button type="button" @click="cancelTimer()" class="mt-4 text-sm font-semibold text-gray-500 hover:text-primary-dark cursor-pointer">Cancel</button>
            </div>
        </div>

        {{-- ==================== DURATION CHOICE MODAL ==================== --}}
        <div x-show="durationPrompt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="cancelTimer()"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
                <h3 class="text-lg font-extrabold text-ink">How much time?</h3>
                <div class="mt-5 grid grid-cols-3 gap-2">
                    <template x-for="m in [1, 3, 5, 7, 10]" :key="m">
                        <button type="button" @click="setDuration(m)"
                                class="rounded-xl border-2 border-primary/20 px-3 py-3 text-base font-extrabold text-primary-dark transition-colors duration-200 hover:border-primary hover:bg-primary/5 cursor-pointer">
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
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary">
                        <button type="button" @click="confirmCustom()"
                                class="shrink-0 rounded-xl bg-primary px-4 py-2 text-sm font-extrabold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                            Go
                        </button>
                    </div>
                    <p x-show="customError" x-cloak class="mt-1 text-left text-xs text-danger" x-text="customError"></p>
                </div>

                <button type="button" @click="durationPrompt = false; timerPrompt = true"
                        class="mt-5 text-sm font-semibold text-gray-500 hover:text-primary-dark cursor-pointer">
                    &larr; Back
                </button>
            </div>
        </div>

        {{-- ====================== REVIEW-AGAIN MODAL ====================== --}}
        <div x-show="reviewPrompt" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="reviewPrompt = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
                <h3 class="text-lg font-extrabold text-ink">Look through your answers again?</h3>
                <p class="mt-2 text-sm text-gray-600">You can revisit each word and change your spelling before you finish.</p>
                <div class="mt-6 flex flex-col gap-2">
                    <button type="button" @click="reviewAgain()"
                            class="rounded-xl border-2 border-primary px-5 py-3 text-base font-extrabold text-primary-dark transition-colors duration-200 hover:bg-primary/5 cursor-pointer">
                        Yes, let me check
                    </button>
                    <button type="button" @click="submit()"
                            class="rounded-xl bg-primary px-5 py-3 text-base font-extrabold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        No, I'm done
                    </button>
                </div>
            </div>
        </div>

        {{-- hidden POST form, submitted programmatically --}}
        <form method="POST" action="{{ route('student.games.spelling.finish') }}" x-ref="finishForm" class="hidden">
            @csrf
            <input type="hidden" name="level" :value="chosenLevel">
            <template x-for="(w, i) in words" :key="'f-' + i">
                <span>
                    <input type="hidden" :name="`items[${i}][shown]`" :value="w.shown">
                    <input type="hidden" :name="`items[${i}][response]`" :value="letters[i].join('')">
                </span>
            </template>
        </form>
    </div>

    <script>
        function spellingGame() {
            return {
                // server data
                wordsByLevel:  @js($wordsByLevel),
                backgrounds:   @js($backgrounds),
                perRound:        @js($perRound),
                perRoundByLevel: @js($perRoundByLevel),
                mixedLevel:    @js($mixedLevel),
                mixedPerRound: @js($mixedPerRound),

                // ui state
                screen: 'loading',     // loading | level | play
                stage: 'school',       // school | levels
                school: null,
                dots: '',
                reviewPrompt: false,

                // timer selection
                timerPrompt: false,
                durationPrompt: false,
                pendingLevel: null,
                customMinutes: '',
                customError: '',

                // round state
                chosenLevel: null,
                words: [],
                letters: [],
                current: 0,
                bg: null,

                // timer runtime
                timed: false,
                remaining: null,
                sirenOn: false,
                _timer: null,
                _submitting: false,

                boot() {
                    let n = 0;
                    this._dotTimer = setInterval(() => { n = (n + 1) % 4; this.dots = '.'.repeat(n); }, 350);
                    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    setTimeout(() => { this.screen = 'level'; clearInterval(this._dotTimer); }, reduce ? 400 : 1800);
                },

                chooseSchool(s) { this.school = s; this.stage = 'levels'; },

                // Primary levels offer a timer; Secondary go straight in.
                pickLevel(label, group) {
                    this.pendingLevel = label;
                    if (group === 'Primary') {
                        this.timerPrompt = true;
                    } else {
                        this.startLevel(label, null);
                    }
                },

                cancelTimer() { this.timerPrompt = false; this.durationPrompt = false; this.pendingLevel = null; },
                chooseInfinite() { this.timerPrompt = false; this.startLevel(this.pendingLevel, null); },
                chooseTimed() { this.timerPrompt = false; this.durationPrompt = true; },
                setDuration(mins) { this.durationPrompt = false; this.startLevel(this.pendingLevel, mins); },
                confirmCustom() {
                    const m = parseInt(this.customMinutes, 10);
                    if (!Number.isInteger(m) || m < 1 || m > 180) {
                        this.customError = 'Enter a whole number of minutes (1–180).';
                        return;
                    }
                    this.customError = '';
                    this.setDuration(m);
                },

                questionCount(label) {
                    if (label === this.mixedLevel) return this.mixedPerRound;
                    return this.perRoundByLevel[label] ?? this.perRound;
                },

                startLevel(label, minutes) {
                    const pool = (this.wordsByLevel[label] || []).slice();
                    for (let i = pool.length - 1; i > 0; i--) {
                        const k = Math.floor(Math.random() * (i + 1));
                        [pool[i], pool[k]] = [pool[k], pool[i]];
                    }
                    this.words = pool.slice(0, Math.min(this.questionCount(label), pool.length));
                    this.letters = this.words.map(w => Array(w.length).fill(''));
                    this.current = 0;
                    this.chosenLevel = label;
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
                    this.$nextTick(() => this.focusFirst());
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

                focusFirst() {
                    const first = this.$refs.boxes?.querySelector('input');
                    if (first) first.focus();
                },

                onInput(e) {
                    if (e.target.value && e.target.nextElementSibling) e.target.nextElementSibling.focus();
                },

                onKey(e, j) {
                    if (e.key === 'Enter') { e.preventDefault(); this.advance(); return; }
                    if (e.key === 'Backspace' && e.target.value === '' && e.target.previousElementSibling) {
                        e.preventDefault();
                        e.target.previousElementSibling.focus();
                        e.target.previousElementSibling.select?.();
                    }
                    if (e.key === 'ArrowLeft' && e.target.previousElementSibling) { e.preventDefault(); e.target.previousElementSibling.focus(); }
                    if (e.key === 'ArrowRight' && e.target.nextElementSibling) { e.preventDefault(); e.target.nextElementSibling.focus(); }
                },

                advance() {
                    if (this.current < this.words.length - 1) {
                        this.current++;
                        this.$nextTick(() => this.focusFirst());
                    } else {
                        this.reviewPrompt = true;
                    }
                },

                reviewAgain() {
                    this.reviewPrompt = false;
                    this.current = 0;
                    this.$nextTick(() => this.focusFirst());
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
