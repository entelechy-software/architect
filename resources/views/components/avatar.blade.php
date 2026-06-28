@props([
    'src'  => null,
    'name' => '',
    'size' => 'md',
])

@php
    // Deterministic background colour from name — cycles through 8 hues
    $hues  = [0, 30, 60, 120, 180, 210, 240, 300];
    $index = strlen($name) > 0 ? (ord($name[0]) % count($hues)) : 0;
    $hue   = $hues[$index];

    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn($w) => strtoupper($w[0]))
        ->implode('');
@endphp

<span class="arch-avatar"
      data-size="{{ $size }}"
      {{ $attributes }}>
    @if($src)
        <img class="arch-avatar__img" src="{{ $src }}" alt="{{ $name }}">
    @else
        <span class="arch-avatar__initials"
              style="background-color: hsl({{ $hue }}, 60%, 45%);">
            {{ $initials ?: '?' }}
        </span>
    @endif
</span>
