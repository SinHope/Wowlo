@props(['status'])

@php
    $done = $status === 'done';
@endphp

<span @class([
    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold',
    'bg-success/10 text-success' => $done,
    'bg-accent/15 text-accent-dark' => ! $done,
])>
    @if ($done)
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
        Done
    @else
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
        Pending
    @endif
</span>
