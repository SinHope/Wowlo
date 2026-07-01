<x-app-layout>
    <x-slot name="header">Hangman Wheel Panda</x-slot>

    <div
        x-data="hangmanGame()"
        x-init="boot()"
        @keydown.window="onKeydown($event)"
        @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
        class="mx-auto max-w-4xl"
    >
        {{-- ============================ LOADING ============================ --}}
        <div x-show="screen === 'loading'" x-cloak
             class="flex flex-col items-center justify-center text-center" style="min-height: calc(100vh - 9rem)">
            {{-- spinning panda mascot --}}
            <svg viewBox="0 0 120 120" class="h-32 w-32 animate-spin drop-shadow-lg motion-reduce:animate-none"
                 style="animation-duration: 1.6s" aria-hidden="true">
                <circle cx="35" cy="32" r="15" fill="#1f2937"/>
                <circle cx="85" cy="32" r="15" fill="#1f2937"/>
                <circle cx="60" cy="62" r="40" fill="#fff" stroke="#111827" stroke-width="3"/>
                <ellipse cx="46" cy="58" rx="11" ry="14" fill="#1f2937" transform="rotate(18 46 58)"/>
                <ellipse cx="74" cy="58" rx="11" ry="14" fill="#1f2937" transform="rotate(-18 74 58)"/>
                <circle cx="48" cy="60" r="4" fill="#fff"/>
                <circle cx="72" cy="60" r="4" fill="#fff"/>
                <ellipse cx="60" cy="76" rx="7" ry="5" fill="#1f2937"/>
                <path d="M60 81 L60 86 M50 86 Q60 96 70 86" fill="none" stroke="#111827" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <p class="mt-6 text-xl font-extrabold tracking-wide text-emerald-700">
                Warming Up<span x-text="dots"></span>
            </p>
        </div>

        {{-- top bar: manage wheels link (tutors/admin) --}}
        @if ($canManageWheels)
            <div class="mb-4 flex justify-end" x-show="screen !== 'loading'" x-cloak>
                <a href="{{ route('tutor.games.hangman.wheels.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-primary-dark">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    Manage wheels
                </a>
            </div>
        @endif

        {{-- ============================= SETUP ============================= --}}
        <div x-show="screen === 'setup'" x-cloak
             class="flex flex-col items-center justify-center text-center" style="min-height: calc(100vh - 13rem)">
            <div class="text-6xl">🐼</div>
            <h2 class="mt-4 text-3xl font-extrabold text-ink">Hangman Wheel Panda</h2>
            <p class="mt-2 max-w-md text-sm text-gray-600">
                Spin the wheel, then guess letters to find the secret word.
                Each wrong guess draws a piece of the panda — don’t let it finish!
            </p>

            @if ($wheels->isEmpty())
                <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 text-sm font-semibold text-amber-800">
                    No wheels are available yet. @if ($canManageWheels) Create one first. @else Ask your tutor to set one up. @endif
                </div>
            @else
                <p class="mt-6 rounded-xl bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                    You’ll get a <span class="font-extrabold">random puzzle</span> — its category is shown as a hint while you play.
                </p>

                <div class="mt-4 w-full max-w-sm text-left">
                    <label class="block text-sm font-bold text-ink">Pick a wheel</label>
                    <select x-model="selectedWheelId"
                            class="mt-1 block w-full rounded-xl border-gray-300 text-base font-semibold shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach ($wheels as $wheel)
                            <option value="{{ $wheel->id }}">{{ $wheel->name }}@if ($wheel->isStandard()) (Standard)@endif</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" @click="start()" :disabled="busy"
                        class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-emerald-500 px-8 py-3.5 text-lg font-extrabold text-white shadow-sm transition-colors hover:bg-emerald-600 disabled:opacity-60 cursor-pointer">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z"/></svg>
                    Start Playing
                </button>

                <p x-show="error" x-cloak class="mt-4 text-sm font-semibold text-danger" x-text="error"></p>
            @endif
        </div>

        {{-- ============================= PLAY ============================= --}}
        <div x-show="screen === 'playing'" x-cloak>
            <div class="grid gap-6 lg:grid-cols-2">

                {{-- LEFT: the panda --}}
                <div class="rounded-3xl border border-gray-100 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-extrabold text-emerald-700">The Panda</span>
                        <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-extrabold tabular-nums"
                              :class="wrong >= maxWrong - 2 ? 'text-danger' : 'text-gray-600'">
                            Wrong: <span x-text="wrong"></span> / <span x-text="maxWrong"></span>
                        </span>
                    </div>

                    {{-- panda SVG: part N appears once wrong >= N (face → body → hands → legs → eyes → nose → mouth) --}}
                    <div class="mx-auto mt-2 w-full max-w-[18rem]">
                        <svg viewBox="0 0 220 330" class="h-auto w-full" aria-hidden="true">
                            {{-- guide ground --}}
                            <line x1="40" y1="322" x2="180" y2="322" stroke="#d1d5db" stroke-width="3" stroke-linecap="round"/>

                            {{-- 2 body (drawn first so head/limbs sit on top) --}}
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=2?1:0}`">
                                <ellipse cx="110" cy="208" rx="58" ry="66" fill="#fff" stroke="#111827" stroke-width="4"/>
                                <ellipse cx="110" cy="225" rx="34" ry="40" fill="#f3f4f6"/>
                            </g>

                            {{-- 5 left leg / 6 right leg --}}
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=5?1:0}`">
                                <ellipse cx="86" cy="284" rx="19" ry="28" fill="#1f2937"/>
                            </g>
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=6?1:0}`">
                                <ellipse cx="134" cy="284" rx="19" ry="28" fill="#1f2937"/>
                            </g>

                            {{-- 3 left hand / 4 right hand --}}
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=3?1:0}`">
                                <ellipse cx="56" cy="192" rx="16" ry="30" fill="#1f2937" transform="rotate(34 56 192)"/>
                            </g>
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=4?1:0}`">
                                <ellipse cx="164" cy="192" rx="16" ry="30" fill="#1f2937" transform="rotate(-34 164 192)"/>
                            </g>

                            {{-- 1 face (ears + head) --}}
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=1?1:0}`">
                                <circle cx="72" cy="42" r="20" fill="#1f2937"/>
                                <circle cx="148" cy="42" r="20" fill="#1f2937"/>
                                <circle cx="110" cy="86" r="54" fill="#fff" stroke="#111827" stroke-width="4"/>
                            </g>

                            {{-- 7 left eye / 8 right eye (panda patches) --}}
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=7?1:0}`">
                                <ellipse cx="90" cy="82" rx="15" ry="19" fill="#1f2937" transform="rotate(18 90 82)"/>
                                <circle cx="92" cy="84" r="6" fill="#fff"/>
                                <circle cx="93" cy="85" r="3" fill="#111827"/>
                            </g>
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=8?1:0}`">
                                <ellipse cx="130" cy="82" rx="15" ry="19" fill="#1f2937" transform="rotate(-18 130 82)"/>
                                <circle cx="128" cy="84" r="6" fill="#fff"/>
                                <circle cx="127" cy="85" r="3" fill="#111827"/>
                            </g>

                            {{-- 9 nose --}}
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=9?1:0}`">
                                <ellipse cx="110" cy="104" rx="10" ry="7" fill="#1f2937"/>
                            </g>

                            {{-- 10 mouth (once drawn, the panda — and the round — is complete: a loss) --}}
                            <g style="transition:opacity .4s" :style="`opacity:${wrong>=10?1:0}`">
                                <path d="M110 111 L110 118 M94 118 Q110 134 126 118" fill="none" stroke="#111827" stroke-width="3" stroke-linecap="round"/>
                            </g>
                        </svg>
                    </div>
                </div>

                {{-- RIGHT: the wheel --}}
                <div>
                    {{-- the wheel --}}
                    <div class="rounded-3xl border border-gray-100 bg-white p-5 text-center shadow-sm">
                        <div class="relative mx-auto h-72 w-72">
                            {{-- pointer --}}
                            <div class="absolute left-1/2 top-0 z-10 -translate-x-1/2 -translate-y-1">
                                <svg width="30" height="30" viewBox="0 0 24 24" class="drop-shadow"><path d="M12 22 L3 5 L21 5 Z" fill="#ef4444"/></svg>
                            </div>
                            {{-- disc (slice labels live inside so they spin with it) --}}
                            <div class="relative h-72 w-72 overflow-hidden rounded-full border-4 border-white shadow-inner ring-2 ring-gray-200"
                                 :style="wheelStyle">
                                <template x-for="(s, i) in slices" :key="i">
                                    <div class="absolute left-1/2 top-1/2 flex items-center" :style="sliceLabelStyle(i)">
                                        <span class="block truncate font-extrabold leading-none"
                                              style="width: 108px; padding-left: 20px; font-size: 12px; color: #064e3b;"
                                              x-text="s"></span>
                                    </div>
                                </template>
                            </div>
                            {{-- hub --}}
                            <div class="absolute left-1/2 top-1/2 z-10 flex h-16 w-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white text-2xl shadow ring-2 ring-gray-200">🐼</div>
                        </div>

                        <button type="button" @click="spin()" :disabled="spinning || status !== 'playing' || guessesLeft > 0 || busy"
                                class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-amber-500 px-7 py-3 text-base font-extrabold text-white shadow-sm transition-colors hover:bg-amber-600 disabled:opacity-50 cursor-pointer">
                            <span x-text="spinning ? 'Spinning…' : 'Spin the Wheel'"></span>
                        </button>

                        {{-- landed slice + what it did --}}
                        <div x-show="landedText" x-cloak x-transition
                             class="mt-3 rounded-xl bg-amber-50 px-4 py-2 text-sm font-extrabold text-amber-800">
                            🎡 <span x-text="landedText"></span>
                            <span x-show="turnMsg" class="mt-0.5 block text-xs font-bold text-amber-700" x-text="turnMsg"></span>
                        </div>

                        {{-- whose turn / guesses left --}}
                        <div class="mt-3 text-sm font-extrabold"
                             :class="guessesLeft > 0 ? 'text-emerald-700' : 'text-gray-500'">
                            <span x-show="guessesLeft > 0" x-cloak>Guesses left this spin: <span x-text="guessesLeft"></span></span>
                            <span x-show="guessesLeft <= 0 && status === 'playing'" x-cloak>👉 Spin the wheel to earn a guess!</span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- FULL-WIDTH: the word / phrase (big letters, plenty of room) --}}
            <div class="mt-6 rounded-3xl border border-gray-100 bg-white p-6 text-center shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                    Guess the puzzle
                    <span x-show="category" x-cloak class="ml-1 font-extrabold text-emerald-600">· <span x-text="category"></span></span>
                </p>
                {{-- each word stays together; spaces become gaps between words --}}
                <div class="mt-4 flex flex-wrap justify-center gap-x-6 gap-y-4">
                    <template x-for="(word, wi) in maskWords" :key="wi">
                        <div class="flex gap-2">
                            <template x-for="(ch, ci) in word" :key="ci">
                                <span :class="isLetterSlot(ch)
                                        ? 'flex h-16 w-11 items-end justify-center border-b-4 border-emerald-400 text-4xl font-extrabold text-ink sm:h-20 sm:w-14 sm:text-5xl'
                                        : 'flex h-16 w-5 items-end justify-center text-4xl font-extrabold text-gray-400 sm:h-20 sm:text-5xl'"
                                      x-text="ch ?? ''"></span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- FULL-WIDTH: letters + solve --}}
            <div class="mt-4 rounded-3xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
                <div class="mx-auto grid max-w-2xl grid-cols-7 gap-1.5 sm:grid-cols-9 sm:gap-2">
                    <template x-for="ch in alphabet" :key="ch">
                        <button type="button" @click="guessLetter(ch)"
                                :disabled="guessed.includes(ch) || status !== 'playing' || busy || guessesLeft <= 0"
                                class="aspect-square rounded-lg text-base font-extrabold transition-colors cursor-pointer disabled:cursor-not-allowed sm:text-lg"
                                :class="letterClass(ch)"
                                x-text="ch"></button>
                    </template>
                </div>

                {{-- solve --}}
                <div class="mx-auto mt-4 flex max-w-2xl gap-2">
                    <input type="text" x-model="solveInput" maxlength="60"
                           :disabled="status !== 'playing' || busy || guessesLeft <= 0"
                           @keydown.enter.prevent="solve()"
                           placeholder="Know it? Type the whole answer"
                           class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:bg-gray-50">
                    <button type="button" @click="solve()" :disabled="status !== 'playing' || busy || guessesLeft <= 0 || !solveInput.trim()"
                            class="shrink-0 rounded-xl bg-emerald-500 px-5 py-2 text-sm font-extrabold text-white transition-colors hover:bg-emerald-600 disabled:opacity-50 cursor-pointer">
                        Solve
                    </button>
                </div>
                <p class="mt-2 text-center text-xs text-gray-400">
                    <span x-show="guessesLeft > 0">Tip: press a letter key on your keyboard to guess</span>
                    <span x-show="guessesLeft <= 0">Spin the wheel first — then guess your letters.</span>
                </p>
            </div>

            {{-- ===================== WIN / LOSE OVERLAY ===================== --}}
            <div x-show="status !== 'playing'" x-cloak
                 class="fixed inset-0 z-[80] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40"></div>
                <div class="relative w-full max-w-sm rounded-3xl bg-white p-7 text-center shadow-xl">
                    <template x-if="status === 'won'">
                        <div>
                            <div class="text-5xl">🎉🐼</div>
                            <h3 class="mt-3 text-2xl font-extrabold text-emerald-600">You solved it!</h3>
                            <p class="mt-2 text-sm text-gray-600">The answer was</p>
                            <p class="mt-1 text-2xl font-extrabold tracking-wide text-ink" x-text="word"></p>
                        </div>
                    </template>
                    <template x-if="status === 'lost'">
                        <div>
                            <div class="text-5xl">🐼💔</div>
                            <h3 class="mt-3 text-2xl font-extrabold text-danger">The panda is complete…</h3>
                            <p class="mt-2 text-sm text-gray-600">The answer was</p>
                            <p class="mt-1 text-2xl font-extrabold tracking-wide text-ink" x-text="word"></p>
                        </div>
                    </template>

                    <div class="mt-6 flex flex-col gap-2">
                        <button type="button" @click="start()" :disabled="busy"
                                class="rounded-xl bg-emerald-500 px-5 py-3 text-base font-extrabold text-white transition-colors hover:bg-emerald-600 disabled:opacity-60 cursor-pointer">
                            Play Again
                        </button>
                        <button type="button" @click="screen = 'setup'"
                                class="rounded-xl border-2 border-gray-200 px-5 py-3 text-base font-extrabold text-gray-600 transition-colors hover:bg-gray-50 cursor-pointer">
                            Change Wheel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const HW_ROUTES = {
            csrf:  '{{ csrf_token() }}',
            start: '{{ route('games.hangman.start') }}',
            guess: '{{ route('games.hangman.guess') }}',
            effect: '{{ route('games.hangman.effect') }}',
        };

        function hangmanGame() {
            return {
                // server data
                wheels: @js($wheels),
                maxWrong: @js($maxWrong),

                // ui
                screen: 'loading',        // loading | setup | playing
                dots: '',
                error: '',
                busy: false,

                // category (chosen randomly by the server; shown as a hint)
                category: '',

                // wheel
                selectedWheelId: @js($wheels->first()->id ?? null),
                slices: [],
                rotation: 0,
                spinning: false,
                spinDur: 4.2,
                landedText: null,
                turnMsg: '',
                guessesLeft: 0,     // letters you may guess right now (0 = must spin)

                // round (mirrors the server's public state)
                mask: [],
                guessed: [],
                wrong: 0,
                status: 'playing',
                word: null,
                solveInput: '',

                alphabet: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split(''),
                _audioCtx: null,

                boot() {
                    if (this.reduceMotion()) this.spinDur = 0.3;
                    // Panda spinner loader → setup, mirroring the other games.
                    let n = 0;
                    this._dotTimer = setInterval(() => { n = (n + 1) % 4; this.dots = '.'.repeat(n); }, 350);
                    setTimeout(() => {
                        this.screen = 'setup';
                        clearInterval(this._dotTimer);
                    }, this.reduceMotion() ? 400 : 1600);
                },

                reduceMotion() {
                    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                },

                // ---- server round ----------------------------------------
                async start() {
                    if (this.busy) return;
                    this.error = '';
                    const wheel = this.wheels.find(w => String(w.id) === String(this.selectedWheelId)) || this.wheels[0];
                    if (!wheel) { this.error = 'No wheel available.'; return; }
                    this.slices = Array.isArray(wheel.slices) ? wheel.slices : [];
                    this.rotation = 0;
                    this.landedText = null;
                    this.turnMsg = '';
                    this.guessesLeft = 0;   // must spin before guessing
                    this.solveInput = '';

                    this.busy = true;
                    try {
                        const state = await this.post(HW_ROUTES.start, {}); // server picks a random category
                        this.apply(state);
                        this.screen = 'playing';
                    } catch (e) {
                        this.error = 'Could not start the game. Please try again.';
                    } finally {
                        this.busy = false;
                    }
                },

                async guessLetter(ch) {
                    if (this.status !== 'playing' || this.busy || this.guessesLeft <= 0 || this.guessed.includes(ch)) return;
                    await this.send({ letter: ch });
                    this.guessesLeft = Math.max(0, this.guessesLeft - 1); // each guess costs a turn
                },

                async solve() {
                    const w = this.solveInput.trim();
                    if (this.status !== 'playing' || this.busy || this.guessesLeft <= 0 || !w) return;
                    // strip to letters — the server only accepts alpha
                    const clean = w.replace(/[^a-zA-Z]/g, '');
                    if (!clean) { this.solveInput = ''; return; }
                    await this.send({ word: clean });
                    this.guessesLeft = Math.max(0, this.guessesLeft - 1);
                    this.solveInput = '';
                },

                async send(body) {
                    this.busy = true;
                    const before = this.wrong;
                    try {
                        const state = await this.post(HW_ROUTES.guess, body);
                        this.apply(state);
                        if (this.status === 'won') this.playWin();
                        else if (this.status === 'lost') this.playLose();
                        else if (this.wrong > before) this.playBlip(false);
                        else this.playBlip(true);
                    } catch (e) {
                        this.error = 'Something went wrong. Please try again.';
                    } finally {
                        this.busy = false;
                    }
                },

                apply(state) {
                    this.mask = state.mask ?? [];
                    this.guessed = state.guessed ?? [];
                    this.wrong = state.wrong ?? 0;
                    this.maxWrong = state.maxWrong ?? this.maxWrong;
                    this.status = state.status ?? 'playing';
                    this.word = state.word ?? null;
                    this.category = state.category ?? '';
                },

                // Split the mask into words (by spaces) so each word's blanks stay
                // together and only the gaps between words can wrap.
                get maskWords() {
                    const words = [];
                    let cur = [];
                    this.mask.forEach((ch) => {
                        if (ch === ' ') { words.push(cur); cur = []; }
                        else cur.push(ch);
                    });
                    words.push(cur);
                    return words;
                },

                // A guessable slot: an un-revealed letter (null) or a revealed A–Z
                // letter. Punctuation (apostrophe/hyphen) is shown, not a blank.
                isLetterSlot(ch) {
                    return ch === null || /^[A-Za-z]$/.test(ch);
                },

                async post(url, body) {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': HW_ROUTES.csrf,
                        },
                        body: JSON.stringify(body || {}),
                    });
                    if (!res.ok) throw new Error('Request failed: ' + res.status);
                    return res.json();
                },

                // ---- keyboard --------------------------------------------
                onKeydown(e) {
                    if (this.screen !== 'playing') return;
                    const tag = e.target.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                    if (/^[a-zA-Z]$/.test(e.key)) {
                        this.guessLetter(e.key.toUpperCase());
                    }
                },

                // ---- letter colours --------------------------------------
                letterClass(ch) {
                    if (!this.guessed.includes(ch)) {
                        return 'bg-gray-100 text-gray-700 hover:bg-emerald-100';
                    }
                    return this.mask.includes(ch)
                        ? 'bg-emerald-500 text-white'
                        : 'bg-rose-400 text-white opacity-70';
                },

                // ---- the wheel -------------------------------------------
                get wheelStyle() {
                    const n = Math.max(this.slices.length, 1);
                    const seg = 360 / n;
                    const colors = ['#34d399', '#10b981', '#5eead4', '#2dd4bf', '#6ee7b7'];
                    const stops = [];
                    for (let i = 0; i < n; i++) {
                        const c = colors[i % colors.length];
                        stops.push(`${c} ${i * seg}deg ${(i + 1) * seg}deg`);
                    }
                    return `background: conic-gradient(${stops.join(',')});`
                        + `transform: rotate(${this.rotation}deg);`
                        + `transition: transform ${this.spinDur}s cubic-bezier(.17,.67,.22,1);`;
                },

                // Position each label along its slice's spoke, reading center→rim.
                sliceLabelStyle(i) {
                    const n = Math.max(this.slices.length, 1);
                    const seg = 360 / n;
                    const center = i * seg + seg / 2;   // clockwise from the top (pointer)
                    return `transform-origin: 0 0; transform: rotate(${center - 90}deg);`
                        + `height: 16px; margin-top: -8px;`;
                },

                spin() {
                    if (this.spinning || this.status !== 'playing' || this.guessesLeft > 0 || this.busy) return;
                    const n = Math.max(this.slices.length, 1);
                    const seg = 360 / n;
                    const i = Math.floor(Math.random() * n);
                    const targetCenter = i * seg + seg / 2;
                    const desiredMod = (360 - targetCenter) % 360;
                    const currentMod = ((this.rotation % 360) + 360) % 360;
                    const turns = this.reduceMotion() ? 1 : 6;
                    const delta = (((desiredMod - currentMod) % 360) + 360) % 360 + turns * 360;

                    this.spinning = true;
                    this.landedText = null;
                    this.turnMsg = '';
                    this.rotation += delta;
                    this.playSpin(this.spinDur);

                    setTimeout(() => {
                        this.spinning = false;
                        this.landedText = this.slices[i] ?? null;
                        this.applyEffect(this.landedText);
                    }, this.spinDur * 1000 + 80);
                },

                // Turn what the wheel landed on into a real effect. Recognized
                // phrases (the suggestion chips) do something; any other custom
                // text is just flavour worth one normal guess.
                async applyEffect(text) {
                    const key = (text || '').trim().toLowerCase();

                    if (key === '+1 free guess') {
                        this.guessesLeft = 2; this.turnMsg = 'Nice — 2 guesses this spin!';
                    } else if (key === '+2 free guesses' || key === 'double trouble') {
                        this.guessesLeft = 3; this.turnMsg = 'Wow — 3 guesses this spin!';
                    } else if (key === 'reveal a letter' || key === 'mystery letter') {
                        await this.serverEffect('reveal'); this.afterReveal('A letter was revealed — plus 1 guess.');
                    } else if (key === 'free vowel') {
                        await this.serverEffect('reveal_vowel'); this.afterReveal('A free vowel — plus 1 guess.');
                    } else if (key === 'lose a guess') {
                        await this.serverEffect('lose_guess');
                        if (this.status === 'playing') { this.guessesLeft = 1; this.turnMsg = 'Ouch! A panda part is drawn — but take 1 guess.'; }
                    } else if (key === 'spin again') {
                        this.guessesLeft = 0; this.turnMsg = 'Spin again!';
                    } else if (key === 'skip a turn') {
                        this.guessesLeft = 0; this.turnMsg = 'Turn skipped — spin again.';
                    } else {
                        this.guessesLeft = 1; this.turnMsg = '1 guess this spin.';
                    }
                },

                afterReveal(msg) {
                    if (this.status === 'playing') { this.guessesLeft = 1; this.turnMsg = msg; }
                },

                async serverEffect(type) {
                    this.busy = true;
                    try {
                        const state = await this.post(HW_ROUTES.effect, { type });
                        this.apply(state);
                        if (this.status === 'won') this.playWin();
                        else if (this.status === 'lost') this.playLose();
                        else this.playBlip(type !== 'lose_guess');
                    } catch (e) {
                        this.error = 'Something went wrong. Please try again.';
                    } finally {
                        this.busy = false;
                    }
                },

                // ---- sound (Web Audio, no asset files) -------------------
                audio() {
                    if (this.reduceMotion()) return null;
                    try {
                        const Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) return null;
                        this._audioCtx = this._audioCtx || new Ctx();
                        if (this._audioCtx.state === 'suspended') this._audioCtx.resume();
                        return this._audioCtx;
                    } catch (_) { return null; }
                },

                tone(ctx, freq, at, dur, type = 'sine', peak = 0.2) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = type;
                    osc.frequency.setValueAtTime(freq, at);
                    gain.gain.setValueAtTime(0.0001, at);
                    gain.gain.exponentialRampToValueAtTime(peak, at + 0.01);
                    gain.gain.exponentialRampToValueAtTime(0.0001, at + dur);
                    osc.connect(gain).connect(ctx.destination);
                    osc.start(at);
                    osc.stop(at + dur + 0.02);
                },

                // Wheel-of-Fortune ratchet: clicks that slow down over the spin.
                playSpin(dur) {
                    const ctx = this.audio();
                    if (!ctx) return;
                    let t = 0, gap = 0.04;
                    while (t < dur) {
                        this.tone(ctx, 900, ctx.currentTime + t, 0.04, 'square', 0.1);
                        t += gap;
                        gap *= 1.07; // widening gaps → deceleration
                    }
                },

                playWin() {
                    const ctx = this.audio();
                    if (!ctx) return;
                    [523.25, 659.25, 783.99, 1046.5].forEach((f, i) => {
                        this.tone(ctx, f, ctx.currentTime + i * 0.12, 0.22, 'triangle', 0.22);
                    });
                },

                playLose() {
                    const ctx = this.audio();
                    if (!ctx) return;
                    [392, 329.63, 261.63, 196].forEach((f, i) => {
                        this.tone(ctx, f, ctx.currentTime + i * 0.16, 0.3, 'sawtooth', 0.16);
                    });
                },

                playBlip(correct) {
                    const ctx = this.audio();
                    if (!ctx) return;
                    this.tone(ctx, correct ? 660 : 180, ctx.currentTime, correct ? 0.12 : 0.18,
                        correct ? 'sine' : 'sawtooth', 0.15);
                },
            };
        }
    </script>
</x-app-layout>
