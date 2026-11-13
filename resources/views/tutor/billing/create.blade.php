<x-app-layout>
    <x-slot name="header">WhatsApp Billing</x-slot>

    @include('tutor.billing.partials.form', [
        'bill' => null,
        'initial' => null,
        'title' => 'Generate a Bill',
        'action' => route('tutor.billing.store'),
        'backUrl' => route('tutor.billing.index'),
    ])

    <style>[x-cloak]{display:none!important;}</style>
</x-app-layout>
