{{--
    AJAX single/multi-select combobox. Binds via $wire.set() so Livewire
    stays in sync with the {val, txt} payload needed for lookup coercion.
--}}
@php
    /** @var \Entelechy\Architect\Forms\Fields\LookupField $field */
    $fieldId  = 'field-combobox-' . $field->getName();
    $errorKey = 'formData.' . $field->getName();
@endphp

<x-architect::field-wrapper :field="$field">
    <div id="{{ $fieldId }}"
         class="arch-combobox"
         data-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
         x-data="architectCombobox({
            url: '{{ $field->getSourceUrl() }}',
            multi: {{ $field->isMulti() ? 'true' : 'false' }},
            wireField: 'formData.{{ $field->getName() }}',
         })"
         @click.outside="closeDropdown()">
        <button type="button"
                class="arch-combobox__trigger"
                :class="{ open }"
                @click="toggleDropdown()"
                :aria-expanded="open"
                aria-haspopup="listbox">
            <span class="arch-combobox__value" x-show="hasValue" x-text="selectedLabel"></span>
            <span class="arch-combobox__placeholder" x-show="!hasValue">{{ $field->getPlaceholder() ?? '— Select —' }}</span>
            <span class="arch-combobox__clear" x-show="hasValue" @click.stop="clear()">&times;</span>
            <span class="arch-combobox__chevron"></span>
        </button>

        <div class="arch-combobox__dropdown" x-show="open" x-transition role="listbox">
            <input x-ref="search"
                   type="text"
                   class="arch-combobox__search"
                   x-model="query"
                   @input="onQueryInput()"
                   placeholder="{{ __('Search…') }}"
                   autocomplete="off">

            <ul class="arch-combobox__options">
                <li class="arch-combobox__loading" x-show="loading">{{ __('Loading…') }}</li>
                <template x-for="opt in options" :key="opt.id">
                    <li class="arch-combobox__option"
                        :class="{ selected: isSelected(opt.id) }"
                        @click="select(opt.id, opt.text)"
                        role="option">
                        <span x-text="opt.text"></span>
                    </li>
                </template>
                <li class="arch-combobox__empty" x-show="!loading && options.length === 0 && query">
                    {{ __('No results found.') }}
                </li>
            </ul>
        </div>
    </div>
</x-architect::field-wrapper>
