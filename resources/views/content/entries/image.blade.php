@php
    /** @var \Entelechy\Architect\Content\Entries\ImageEntry $entry */
    /** @var mixed $record */
    $value = $entry->resolveValue($record);
    $src = ($value === null || $value === '') ? $entry->getFallback() : $value;
@endphp
<x-architect::entry-wrapper :entry="$entry">
    @if ($src !== null)
        <img src="{{ $src }}"
             alt="{{ $entry->getLabel() }}"
             class="arch-entry__image"
             @if ($entry->isRounded()) data-rounded="true" @endif
             @if ($entry->getWidth() !== null) width="{{ $entry->getWidth() }}" @endif
             @if ($entry->getHeight() !== null) height="{{ $entry->getHeight() }}" @endif>
    @else
        <span class="arch-entry__placeholder">—</span>
    @endif
</x-architect::entry-wrapper>
