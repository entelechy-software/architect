@php /** @var \Entelechy\Architect\Forms\Fields\SearchWithFiltersField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-search-with-filters"
         wire:ignore
         x-data="architectSearchWithFilters({
            wireField: 'formData.{{ $field->getName() }}',
            availableFilters: @js($field->getAvailableFilters()),
         })">
        <input type="text" class="arch-input" x-ref="search" placeholder="{{ __('Search…') }}">
        <div class="arch-search-with-filters__chips" x-ref="chips"></div>
    </div>
</x-architect::field-wrapper>
