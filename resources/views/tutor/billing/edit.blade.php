<x-app-layout>
    <x-slot name="header">Edit Bill · {{ $bill->student->name }}</x-slot>

    @include('tutor.billing.partials.form', [
        'title' => 'Edit Bill — ' . $bill->billing_month->format('F Y'),
        'action' => route('tutor.billing.update', $bill),
        'backUrl' => route('tutor.billing.show', $bill),
    ])

    <style>[x-cloak]{display:none!important;}</style>
</x-app-layout>
