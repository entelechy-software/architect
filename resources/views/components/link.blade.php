@props([
    'href'     => '#',
    'external' => false,
    'icon'     => null,
])

<a href="{{ $href }}"
   class="arch-link"
   @if($external) target="_blank" rel="noopener noreferrer" @endif
   {{ $attributes }}>
    @if($icon)
        <i class="{{ $icon }}"></i>
    @endif
    {{ $slot }}
    @if($external)
        <i class="fas fa-external-link" style="font-size:0.75em;"></i>
    @endif
</a>
