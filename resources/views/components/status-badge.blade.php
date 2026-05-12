@props(['status'])

@php
    $value = $status instanceof \App\Enums\OrderStatus ? $status->value : $status;
    $classes = match ($value) {
        'placed'     => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        'confirmed'  => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
        'preparing'  => 'bg-sky-100 text-sky-800 ring-1 ring-sky-200',
        'on_the_way' => 'bg-indigo-100 text-indigo-800 ring-1 ring-indigo-200',
        'delivered'  => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200',
        'cancelled'  => 'bg-rose-100 text-rose-800 ring-1 ring-rose-200',
        default      => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
    };
    $label = match ($value) {
        'on_the_way' => 'on the way',
        default => $value,
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center text-xs font-medium px-2.5 py-0.5 rounded-full $classes"]) }}>
    {{ $label }}
</span>
