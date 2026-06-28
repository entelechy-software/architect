@php
    /** @var \Entelechy\Architect\Content\Entries\KeyValueEntry $entry */
    /** @var mixed $record */
    $value = $entry->resolveValue($record);
    $rows = is_array($value) ? $value : [];
@endphp
<x-architect::entry-wrapper :entry="$entry">
    @if ($rows === [])
        <span class="arch-entry__placeholder">—</span>
    @else
        <div class="arch-key-value" data-readonly="true">
            <div class="arch-key-value__header">
                <span>{{ $entry->getKeyLabel() }}</span>
                <span>{{ $entry->getValueLabel() }}</span>
            </div>
            @foreach ($rows as $key => $rowValue)
                <div class="arch-key-value__row">
                    <span>{{ $key }}</span>
                    <span>{{ is_scalar($rowValue) ? $rowValue : json_encode($rowValue) }}</span>
                </div>
            @endforeach
        </div>
    @endif
</x-architect::entry-wrapper>
