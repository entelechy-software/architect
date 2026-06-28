@props([
    'name'  => '',
    'size'  => 'md',
    'color' => null,
])

<span class="arch-icon"
      data-size="{{ $size }}"
      @if($color) data-color="{{ $color }}" @endif
      {{ $attributes }}>
    <i class="{{ $name }}"></i>
</span>
