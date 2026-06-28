@php
    /** @var \Entelechy\Architect\Content\Entries\RepeatableEntry $entry */
    /** @var mixed $record */
    $items = $entry->resolveItems($record);
@endphp
<x-architect::entry-wrapper :entry="$entry">
    @if ($items === [])
        <span class="arch-entry__placeholder">—</span>
    @else
        <div class="arch-grid" data-cols="{{ $entry->getColumns() }}">
            @foreach ($items as $item)
                <div class="arch-entry__repeatable-item">
                    @foreach ($entry->getStructure() as $child)
                        @include('architect::content.partials.structure-item', ['item' => $child, 'record' => $item])
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</x-architect::entry-wrapper>
