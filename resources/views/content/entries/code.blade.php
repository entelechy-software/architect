@php
    /** @var \Entelechy\Architect\Content\Entries\CodeEntry $entry */
    /** @var mixed $record */
    $value = $entry->resolveValue($record);
@endphp
<x-architect::entry-wrapper :entry="$entry">
    @if ($value === null || $value === '')
        <span class="arch-entry__placeholder">—</span>
    @else
        <div class="arch-entry__code"
             data-language="{{ $entry->getLanguage() }}"
             data-line-numbers="{{ $entry->hasLineNumbers() ? 'true' : 'false' }}">
            <pre><code>{{ is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT) }}</code></pre>

            @if ($entry->isCopyable())
                <button type="button"
                        class="arch-entry__copy"
                        x-data
                        x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from(is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT)) }})"
                        title="{{ __('Copy') }}">
                    <i class="fas fa-copy"></i>
                </button>
            @endif
        </div>
    @endif
</x-architect::entry-wrapper>
