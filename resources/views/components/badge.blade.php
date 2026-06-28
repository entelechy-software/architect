@props([
    'color'   => 'gray',
    'variant' => 'soft',
    'size'    => 'md',
])

<span class="arch-badge"
      data-color="{{ $color }}"
      data-variant="{{ $variant }}"
      data-size="{{ $size }}"
      {{ $attributes }}>
    {{ $slot }}
</span>
