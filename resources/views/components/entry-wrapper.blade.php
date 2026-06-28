@props([
    'entry',
])

@php
    $type = Str::kebab(class_basename($entry));
@endphp

<div class="arch-entry" data-type="{{ $type }}">
    <div class="arch-entry__label">{{ $entry->getLabel() }}</div>
    <div class="arch-entry__value">
        {{ $slot }}
    </div>
</div>
