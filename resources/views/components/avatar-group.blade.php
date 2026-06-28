@props([
    'max' => 3,
    'size' => 'md',
    'total' => null,
])

@php
    $overflow = $total !== null ? max(0, $total - $max) : 0;
@endphp

<div class="arch-avatar-group" data-size="{{ $size }}" {{ $attributes }}>
    {{ $slot }}

    @if ($overflow > 0)
        <span class="arch-avatar-group__overflow">+{{ $overflow }}</span>
    @endif
</div>
