@props([
    'label' => '',
    'color' => 'primary',
    'variant' => 'solid',
    'size' => 'md',
])

<div class="arch-split-button"
     data-color="{{ $color }}"
     data-variant="{{ $variant }}"
     data-size="{{ $size }}"
     x-data="{ open: false }"
     @click.outside="open = false">
    <button type="button" class="arch-split-button__primary" {{ $attributes }}>{{ $label }}</button>
    <button type="button" class="arch-split-button__toggle" @click="open = !open">
        <i class="fas fa-chevron-down"></i>
    </button>
    <div class="arch-split-button__menu" x-show="open" x-cloak>
        {{ $slot }}
    </div>
</div>
