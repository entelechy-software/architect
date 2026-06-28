@php
/**
 * @var \Entelechy\Architect\Forms\Fields\CheckboxList $field
 * @var \Closure(string): mixed $get
 */
@endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-checkbox-list"
         data-columns="{{ $field->getColumns() }}"
         @if ($field->isSearchable()) x-data="{ query: '' }" @endif>
        @if ($field->isSearchable())
            <input type="text"
                   class="arch-input arch-checkbox-list__search"
                   x-model="query"
                   placeholder="{{ __('Search…') }}">
        @endif

        <div class="arch-checkbox-list__options">
            @foreach ($field->getOptions($get) as $value => $optionLabel)
                <label class="arch-checkbox"
                       @if ($field->isSearchable()) x-show="query === '' || '{{ \Illuminate\Support\Str::lower($optionLabel) }}'.includes(query.toLowerCase())" @endif>
                    <input type="checkbox"
                           class="arch-checkbox__input"
                           value="{{ $value }}"
                           wire:model="formData.{{ $field->getName() }}">
                    <span class="arch-checkbox__label">{{ $optionLabel }}</span>
                </label>
            @endforeach
        </div>
    </div>
</x-architect::field-wrapper>
