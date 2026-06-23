{{--
    Siren lights — shown at the top of the Spelling game during the final 15
    seconds of a timed round. Converted from the original React + three.js
    fullscreen "police light" snippet (config/sirentlights.php) into a
    lightweight, dependency-free CSS component that fits this Laravel/Tailwind/
    Alpine app (no three.js, no fullscreen WebGL). Same intent: an urgent,
    flashing red/blue police siren.
--}}
<div {{ $attributes->merge(['class' => 'flex items-center justify-center gap-3 rounded-2xl bg-black px-5 py-2.5 shadow-lg']) }}
     role="status" aria-label="Time almost up">
    <span class="siren-orb siren-orb--red"></span>
    <span class="text-sm font-extrabold uppercase tracking-widest text-white">Time's almost up!</span>
    <span class="siren-orb siren-orb--blue"></span>

    <style>
        .siren-orb {
            width: 1rem;
            height: 1rem;
            border-radius: 9999px;
            display: inline-block;
        }
        .siren-orb--red  { background: #ef4444; animation: sirenRed  0.7s steps(1, end) infinite; }
        .siren-orb--blue { background: #3b82f6; animation: sirenBlue 0.7s steps(1, end) infinite; }
        @keyframes sirenRed {
            0%, 49%   { opacity: 0.15; box-shadow: none; }
            50%, 100% { opacity: 1;    box-shadow: 0 0 16px 5px rgba(239, 68, 68, 0.85); }
        }
        @keyframes sirenBlue {
            0%, 49%   { opacity: 1;    box-shadow: 0 0 16px 5px rgba(59, 130, 246, 0.85); }
            50%, 100% { opacity: 0.15; box-shadow: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .siren-orb--red, .siren-orb--blue { animation: none; opacity: 1; }
        }
    </style>
</div>
