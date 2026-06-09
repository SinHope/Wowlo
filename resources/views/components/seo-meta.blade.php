@props([
    'title',
    'description',
    // Default share image (1200×630-style large card). Pages can override per-page.
    'image' => null,
    'type' => 'website',
])

@php
    $ogImage = $image ?? asset('images/og/wowlo-og.png');
    // Self-referencing canonical, query-string stripped (drops ?utm_* etc.).
    $canonical = url()->current();
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph (WhatsApp / Telegram / Facebook / LinkedIn link previews) --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="Wowlo">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:locale" content="en_SG">

{{-- Twitter / X card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">
