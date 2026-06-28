{{--
    AJAX multi-select filter — replaces jQuery/Lookup multi-tag widget.
    Uses Alpine architectCombobox with multiple: true. Zero jQuery dependency.
--}}
@php $filterId = 'filter-multi-' . $filter->name(); @endphp

<div
    id="{{ $filterId }}"
    class="arch-combobox"
    x-data="architectCombobox({
        url:      '{{ $filter->getSource() }}',
        multiple: true,
        value:    $wire.filters[{{ json_encode($filter->name()) }}] ?? [],
        onChange: vs => $wire.call('setFilter', {{ json_encode($filter->name()) }}, vs),
    })"
    x-effect="syncValue($wire.filters[{{ json_encode($filter->name()) }}] ?? [])"
    @click.outside="closeDropdown()"
    @keydown="onKeydown($event)"
>
    {{-- Trigger / chips area --}}
    <div
        class="arch-combobox-trigger"
        :class="{ open }"
        @click.self="openDropdown(); $nextTick(() => $refs.searchInline?.focus())"
        role="combobox"
        :aria-expanded="open"
    >
        {{-- Selected chips --}}
        <template x-for="chip in selectedChips" :key="chip.id">
            <span class="arch-chip">
                <span
                    class="overflow-hidden text-ellipsis whitespace-nowrap"
                    style="max-width:6rem"
                    x-text="chip.text"
                ></span>
                <button
                    type="button"
                    class="arch-chip-remove"
                    @click.stop="removeChip(chip.id)"
                    :aria-label="'Remove ' + chip.text"
                >×</button>
            </span>
        </template>

        {{-- Inline search input --}}
        <input
            x-ref="searchInline"
            type="text"
            class="arch-combobox-search-inline"
            x-model="query"
            @focus="openDropdown()"
            @input="onQueryInput()"
            :placeholder="hasValue ? '' : '{{ __('Search…') }}'"
            autocomplete="off"
            spellcheck="false"
            aria-label="{{ $filter->getLabel() }}"
        >

        {{-- Clear all --}}
        <button
            type="button"
            class="arch-combobox-clear"
            x-show="hasValue"
            @click.stop="clear()"
            title="{{ __('Clear all') }}"
        >×</button>

        <span class="arch-combobox-chevron"><i class="fas fa-chevron-down"></i></span>
    </div>

    {{-- Dropdown --}}
    <div
        class="arch-combobox-dropdown"
        x-show="open"
        x-transition
        role="listbox"
        aria-multiselectable="true"
    >
        <div class="arch-combobox-search-wrap">
            <input
                x-ref="search"
                type="text"
                class="arch-combobox-search"
                x-model="query"
                @input="onQueryInput()"
                placeholder="{{ __('Search…') }}"
                autocomplete="off"
                spellcheck="false"
            >
        </div>
        <ul x-ref="optionList" class="arch-combobox-options">
            <li class="arch-combobox-loading" x-show="loading">
                <i class="fas fa-circle-notch fa-spin text-gray-400"></i>
                {{ __('Loading…') }}
            </li>

            <template x-for="(opt, idx) in options" :key="opt.id">
                <li
                    class="arch-combobox-option"
                    :class="{ active: activeIdx === idx, selected: isSelected(opt.id) }"
                    @click="select(opt.id, opt.text)"
                    :aria-selected="isSelected(opt.id)"
                    role="option"
                >
                    <span class="arch-combobox-check">
                        <i class="fas fa-check" x-show="isSelected(opt.id)"></i>
                        <i class="far fa-square text-gray-300 dark:text-gray-600" x-show="!isSelected(opt.id)"></i>
                    </span>
                    <span x-text="opt.text"></span>
                </li>
            </template>

            <li class="arch-combobox-empty" x-show="!loading && options.length === 0 && query">
                {{ __('No results found.') }}
            </li>
            <li class="arch-combobox-empty" x-show="!loading && options.length === 0 && !query">
                {{ __('Type to search…') }}
            </li>
        </ul>
    </div>
</div>
