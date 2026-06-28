@php
    /** @var \Entelechy\Architect\Content\Entries\IconEntry $entry */
    /** @var mixed $record */
    $value = $entry->resolveValue($record);
@endphp
<x-architect::entry-wrapper :entry="$entry">
    <x-architect::icon
        :name="$entry->resolveIcon($value, $record)"
        :color="$entry->resolveColor($value, $record)"
        :size="$entry->getSize()"
    />
</x-architect::entry-wrapper>
