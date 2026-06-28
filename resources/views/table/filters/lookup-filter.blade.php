{{--
	AJAX single-select filter.
	Uses the Alpine architectCombobox component with the standard lookup endpoint.
--}}
@php $filterId = 'filter-combobox-' . $filter->name(); @endphp

<div
	id="{{ $filterId }}"
	class="arch-combobox"
	x-data="architectCombobox({
		url:     '{{ $filter->getSource() }}',
		value:   $wire.filters[{{ json_encode($filter->name()) }}] ?? null,
		onChange: v => $wire.call('setFilter', {{ json_encode($filter->name()) }}, v ?? ''),
	})"
	x-effect="syncValue($wire.filters[{{ json_encode($filter->name()) }}] ?? null)"
	@click.outside="closeDropdown()"
	@keydown="onKeydown($event)"
>
	<button
		type="button"
		class="arch-combobox-trigger"
		:class="{ open }"
		@click="toggleDropdown()"
		:aria-expanded="open"
		aria-haspopup="listbox"
	>
		<span class="arch-combobox-value" x-show="hasValue" x-text="selectedLabel"></span>
		<span class="arch-combobox-placeholder" x-show="!hasValue">{{ __('All') }}</span>
		<button
			type="button"
			class="arch-combobox-clear"
			x-show="hasValue"
			@click.stop="clear(); $wire.call('setFilter', {{ json_encode($filter->name()) }}, '')"
			title="{{ __('Clear') }}"
			aria-label="{{ __('Clear filter') }}"
		>×</button>
		<span class="arch-combobox-chevron"><i class="fas fa-chevron-down"></i></span>
	</button>

	<div
		class="arch-combobox-dropdown"
		x-show="open"
		x-transition:enter="transition ease-out duration-100"
		x-transition:enter-start="opacity-0 -translate-y-1"
		x-transition:enter-end="opacity-100 translate-y-0"
		x-transition:leave="transition ease-in duration-75"
		x-transition:leave-start="opacity-100 translate-y-0"
		x-transition:leave-end="opacity-0 -translate-y-1"
		role="listbox"
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
				aria-label="{{ __('Search') }}"
			>
		</div>
		<ul x-ref="optionList" class="arch-combobox-options">
			<li
				class="arch-combobox-option"
				:class="{ active: activeIdx === -2, selected: !hasValue }"
				@click="select(null, null); $wire.call('setFilter', {{ json_encode($filter->name()) }}, '')"
				role="option"
			>
				<span class="arch-combobox-check"><i class="fas fa-check" x-show="!hasValue"></i></span>
				<span class="text-gray-500 dark:text-gray-400">{{ __('All') }}</span>
			</li>

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
					</span>
					<span x-text="opt.text"></span>
				</li>
			</template>

			<li class="arch-combobox-empty" x-show="!loading && options.length === 0 && query">
				{{ __('No results found.') }}
			</li>
		</ul>
	</div>
</div>
