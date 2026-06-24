<x-app-layout>
    <x-slot name="header">Games</x-slot>

    @php $diceImg = asset('images/games/roll-the-dice/3d-dice-studio.jpg'); @endphp

    <div
        x-data="{
            rolling: false,
            rx: -20,
            ry: -20,
            audioCtx: null,
            roll() {
                if (this.rolling) return;
                this.rolling = true;
                this.playRattle();
                // Keep spinning forward: several full turns + a random final face.
                this.rx += 360 * (Math.floor(Math.random() * 4) + 3) + 90 * Math.floor(Math.random() * 4);
                this.ry += 360 * (Math.floor(Math.random() * 4) + 3) + 90 * Math.floor(Math.random() * 4);
                setTimeout(() => { this.rolling = false; }, 1500);
            },
            // Synthesised dice rattle: a series of short filtered-noise 'clacks'
            // over the ~1.5s tumble. No audio file or library needed.
            playRattle() {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx) return;
                    this.audioCtx = this.audioCtx || new Ctx();
                    const ctx = this.audioCtx;
                    if (ctx.state === 'suspended') ctx.resume();

                    const clacks = 9;
                    for (let i = 0; i < clacks; i++) {
                        const t = ctx.currentTime + i * (0.10 + Math.random() * 0.06);

                        // Short white-noise burst.
                        const len = Math.floor(ctx.sampleRate * 0.05);
                        const buffer = ctx.createBuffer(1, len, ctx.sampleRate);
                        const data = buffer.getChannelData(0);
                        for (let s = 0; s < len; s++) data[s] = Math.random() * 2 - 1;

                        const src = ctx.createBufferSource();
                        src.buffer = buffer;

                        const band = ctx.createBiquadFilter();
                        band.type = 'bandpass';
                        band.frequency.value = 1800 + Math.random() * 1600;
                        band.Q.value = 1.2;

                        const gain = ctx.createGain();
                        const vol = 0.18 + Math.random() * 0.12;
                        gain.gain.setValueAtTime(0.0001, t);
                        gain.gain.exponentialRampToValueAtTime(vol, t + 0.004);
                        gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.06);

                        src.connect(band).connect(gain).connect(ctx.destination);
                        src.start(t);
                        src.stop(t + 0.08);
                    }
                } catch (e) { /* audio is a nice-to-have; ignore failures */ }
            },
        }"
        class="flex flex-col items-center justify-center px-4"
        style="min-height: calc(100vh - 9rem)"
    >
        {{-- Title with the dice image around it --}}
        <div class="flex items-center justify-center gap-4 sm:gap-8">
            <img src="{{ $diceImg }}" alt="Dice"
                 class="h-16 w-16 rounded-2xl object-cover shadow-md sm:h-24 sm:w-24" />
            <h2 class="text-center text-3xl font-extrabold tracking-tight text-ink sm:text-5xl">
                Roll The Dice
            </h2>
            <img src="{{ $diceImg }}" alt="Dice"
                 class="h-16 w-16 rounded-2xl object-cover shadow-md sm:h-24 sm:w-24" />
        </div>

        {{-- The 3D dice --}}
        <div class="roll-the-dice-scene mt-14 sm:mt-16">
            <div class="roll-the-dice-cube" :class="{ 'is-rolling': rolling }"
                 :style="`transform: rotateX(${rx}deg) rotateY(${ry}deg)`">
                <div class="roll-the-dice-face face-1"><span class="pip p-mc"></span></div>
                <div class="roll-the-dice-face face-2"><span class="pip p-tl"></span><span class="pip p-br"></span></div>
                <div class="roll-the-dice-face face-3"><span class="pip p-tl"></span><span class="pip p-mc"></span><span class="pip p-br"></span></div>
                <div class="roll-the-dice-face face-4"><span class="pip p-tl"></span><span class="pip p-tr"></span><span class="pip p-bl"></span><span class="pip p-br"></span></div>
                <div class="roll-the-dice-face face-5"><span class="pip p-tl"></span><span class="pip p-tr"></span><span class="pip p-mc"></span><span class="pip p-bl"></span><span class="pip p-br"></span></div>
                <div class="roll-the-dice-face face-6"><span class="pip p-tl"></span><span class="pip p-tr"></span><span class="pip p-ml"></span><span class="pip p-mr"></span><span class="pip p-bl"></span><span class="pip p-br"></span></div>
            </div>
        </div>

        <button type="button" @click="roll()" :disabled="rolling"
                class="mt-16 inline-flex cursor-pointer items-center gap-2 rounded-full bg-orange-500 px-10 py-4 text-lg font-extrabold text-white shadow-lg transition hover:bg-orange-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60 sm:mt-20">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            <span x-text="rolling ? 'Rolling…' : 'Roll'"></span>
        </button>
    </div>

    <style>
        .roll-the-dice-scene {
            width: 120px;
            height: 120px;
            perspective: 800px;
        }
        .roll-the-dice-cube {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transition: transform 1.5s cubic-bezier(0.25, 1, 0.4, 1);
        }
        .roll-the-dice-face {
            position: absolute;
            inset: 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            padding: 14px;
            gap: 4px;
            border-radius: 18px;
            background: linear-gradient(145deg, #ffffff, #f1f1f4);
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: inset 0 0 12px rgba(0, 0, 0, 0.08);
            backface-visibility: hidden;
        }
        .pip {
            align-self: center;
            justify-self: center;
            width: 18px;
            height: 18px;
            border-radius: 9999px;
            background: radial-gradient(circle at 35% 30%, #4b5563, #111827);
            box-shadow: inset 0 -1px 2px rgba(0, 0, 0, 0.5);
        }
        .p-tl { grid-area: 1 / 1; }
        .p-tr { grid-area: 1 / 3; }
        .p-ml { grid-area: 2 / 1; }
        .p-mc { grid-area: 2 / 2; }
        .p-mr { grid-area: 2 / 3; }
        .p-bl { grid-area: 3 / 1; }
        .p-br { grid-area: 3 / 3; }

        .face-1 { transform: rotateY(0deg)    translateZ(60px); }
        .face-6 { transform: rotateY(180deg)  translateZ(60px); }
        .face-3 { transform: rotateY(90deg)   translateZ(60px); }
        .face-4 { transform: rotateY(-90deg)  translateZ(60px); }
        .face-2 { transform: rotateX(90deg)   translateZ(60px); }
        .face-5 { transform: rotateX(-90deg)  translateZ(60px); }

        @media (prefers-reduced-motion: reduce) {
            .roll-the-dice-cube { transition: transform 0.4s ease-out; }
        }
    </style>
</x-app-layout>
