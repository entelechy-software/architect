@php
    /** @var \Entelechy\Architect\Content\Entries\ColorEntry $entry */
    /** @var mixed $record */
    $value = $entry->resolveValue($record);
@endphp
<x-architect::entry-wrapper :entry="$entry">
    @if ($value === null || $value === '')
        <span class="arch-entry__placeholder">—</span>
    @else
        <span class="arch-entry__swatch"
              @if ($entry->isCircle()) data-circle="true" @endif
              style="background-color: {{ $value }}"></span>
        @if ($entry->shouldShowHex())
            <span class="arch-entry__swatch-hex">{{ $value }}</span>
        @endif
    @endif
</x-architect::entry-wrapper>
