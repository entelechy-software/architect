{{-- Tabler-styled text filter (LIKE %…%). --}}
<input
    type="text"
    class="arch-input arch-input-sm"
    placeholder="{{ $filter->getLabel() }}"
    x-effect="if (document.activeElement !== $el) $el.value = ($wire.filters['{{ $filter->name() }}'] ?? '')"
    @input.debounce.300ms="$wire.call('setFilter', '{{ $filter->name() }}', $event.target.value)"
    aria-label="{{ $filter->getLabel() }}"
>
