@props([
    'variant' => 'spinner',
    'size' => 'md',
    'color' => 'primary',
])

<div class="arch-loader"
     data-variant="{{ $variant }}"
     data-size="{{ $size }}"
     data-color="{{ $color }}"
     role="status"
     aria-label="Loading"
     {{ $attributes }}>
</div>
