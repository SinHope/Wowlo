{{--
    Shiny gradient button — ported from 21st.dev (kokonutd/button-shiny) to pure
    Blade + Tailwind. The original was a React/shadcn component, but it had no
    React logic: just stacked gradient layers. Spans (not divs) are used because
    <a>/<button> may not legally contain block <div>s.

    Font intentionally follows the Wowlo site (Nunito, font-bold) instead of the
    source's font-light/tracking-tighter, for brand consistency.

    Usage:
        <x-button-shiny :href="route('login')">Log in</x-button-shiny>
        <x-button-shiny type="submit" class="w-full">Get Access</x-button-shiny>
--}}
@props(['href' => null])

@php($tag = $href ? 'a' : 'button')

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->class('group relative inline-flex h-12 items-center justify-center overflow-hidden rounded-lg px-5 transition-all duration-500 cursor-pointer') }}
>
    {{-- Gradient border (2px ring) --}}
    <span class="absolute inset-0 rounded-lg bg-gradient-to-b from-[#654358] via-[#17092A] to-[#2F0D64] p-[2px]">
        <span class="absolute inset-0 rounded-lg bg-[#170928] opacity-90"></span>
    </span>

    {{-- Inner fill + layered gradients (the glow) --}}
    <span class="absolute inset-[2px] rounded-lg bg-[#170928] opacity-95"></span>
    <span class="absolute inset-[2px] rounded-lg bg-gradient-to-r from-[#170928] via-[#1d0d33] to-[#170928] opacity-90"></span>
    <span class="absolute inset-[2px] rounded-lg bg-gradient-to-b from-[#654358]/40 via-[#1d0d33] to-[#2F0D64]/30 opacity-80"></span>
    <span class="absolute inset-[2px] rounded-lg bg-gradient-to-br from-[#C787F6]/10 via-[#1d0d33] to-[#2A1736]/50"></span>
    <span class="absolute inset-[2px] rounded-lg shadow-[inset_0_0_15px_rgba(199,135,246,0.15)]"></span>

    {{-- Label — Nunito (font-sans, inherited) + font-bold for site consistency --}}
    <span class="relative flex items-center justify-center gap-2">
        <span class="bg-gradient-to-b from-[#D69DDE] to-[#B873F8] bg-clip-text text-sm font-bold tracking-tight text-transparent drop-shadow-[0_0_12px_rgba(199,135,246,0.4)]">
            {{ $slot }}
        </span>
    </span>

    {{-- Hover sheen --}}
    <span class="absolute inset-[2px] rounded-lg bg-gradient-to-r from-[#2A1736]/20 via-[#C787F6]/10 to-[#2A1736]/20 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
</{{ $tag }}>
