@props(['amount', 'currency' => 'SGD'])

@php
    $amount = round((float) $amount, 2);
    $owed = $amount > 0.001;
    $credit = $amount < -0.001;
    $abs = number_format(abs($amount), 2);
@endphp

<span @class([
    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold',
    'bg-danger/10 text-danger' => $owed,
    'bg-success/10 text-success' => $credit,
    'bg-gray-100 text-muted' => ! $owed && ! $credit,
])>
    @if ($owed)
        Owes {{ $currency }} {{ $abs }}
    @elseif ($credit)
        Credit {{ $currency }} {{ $abs }}
    @else
        Settled
    @endif
</span>
