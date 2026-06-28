@php
    /** @var \Entelechy\Architect\Content\Entries\TextEntry $entry */
    /** @var mixed $record */
    $value = $entry->resolveValue($record);
@endphp
<x-architect::entry-wrapper :entry="$entry">
    @if ($value === null || $value === '')
        <span class="arch-entry__placeholder">{{ $entry->getPlaceholder() ?? '—' }}</span>
    @elseif ($entry->isBadge())
        <x-architect::badge>{{ $value }}</x-architect::badge>
    @else
        <span>{{ $value }}</span>
    @endif

    @if ($entry->isCopyable() && $value !== null && $value !== '')
        <button type="button"
                class="arch-entry__copy"
                x-data
                x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from((string) $value) }})"
                title="{{ __('Copy') }}">
            <i class="fas fa-copy"></i>
        </button>
    @endif
</x-architect::entry-wrapper>
