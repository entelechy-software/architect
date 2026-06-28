{{--
	AJAX single-select form field.
	Binds via $wire.set() so Livewire stays in sync with the {val, txt}
	payload preserved for lookup coercion.
--}}
@php
	$fieldName  = $field->name();
	$current    = $form[$fieldName] ?? null;
	$currentVal = is_array($current) ? ($current['val'] ?? null) : $current;
	$currentTxt = is_array($current) ? ($current['txt'] ?? '') : '';
	$fieldId    = 'field-combobox-' . $fieldName;
@endphp

<div class="flex flex-col gap-2">
	<label class="text-sm font-medium text-gray-700 dark:text-gray-200" for="{{ $fieldId }}">{{ $field->getLabel() }}</label>

	<div
		id="{{ $fieldId }}"
		class="arch-combobox"
		x-data="architectCombobox({
		url:    '{{ $field->getSourceUrl() }}',
		value:  {{ $currentVal !== null && $currentVal !== '' ? json_encode((string) $currentVal) : 'null' }},
		@if($currentTxt)
		onChange: function(v) {
		@else
		onChange: function(v) {
		@endif
			if (v === null || v === '') {
				$wire.set('form.{{ $fieldName }}', null);
			} else {
				$wire.set('form.{{ $fieldName }}', { val: v, txt: this._labels[v] ?? v });
			}
		},
	})"
	@click.outside="closeDropdown()"
	@keydown="onKeydown($event)"
		>
		<button
			type="button"
			class="arch-combobox-trigger @error('form.'.$fieldName) border-red-400 @enderror"
			:class="{ open }"
			@click="toggleDropdown()"
			:aria-expanded="open"
			aria-haspopup="listbox"
		>
			<span class="arch-combobox-value" x-show="hasValue" x-text="selectedLabel"></span>
			<span class="arch-combobox-placeholder" x-show="!hasValue">{{ __('— Select —') }}</span>
			<span
				role="button"
				tabindex="0"
				class="arch-combobox-clear"
				x-show="hasValue"
				@click.stop="clear()"
				@keydown.enter.stop.prevent="clear()"
				@keydown.space.stop.prevent="clear()"
				title="{{ __('Clear') }}"
			>×</span>
			<span class="arch-combobox-chevron"><i class="fas fa-chevron-down"></i></span>
		</button>

		<div class="arch-combobox-dropdown" x-show="open" x-transition role="listbox">
			<div class="arch-combobox-search-wrap">
				<input
					x-ref="search"
					type="text"
					class="arch-combobox-search"
					x-model="query"
					@input="onQueryInput()"
					placeholder="{{ __('Search…') }}"
					autocomplete="off"
				>
			</div>
			<ul x-ref="optionList" class="arch-combobox-options">
				<li
					class="arch-combobox-option"
					:class="{ selected: !hasValue }"
					@click="select(null)"
				>
					<span class="arch-combobox-check"><i class="fas fa-check" x-show="!hasValue"></i></span>
					<span class="text-gray-400">{{ __('— Select —') }}</span>
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
						<span class="arch-combobox-check"><i class="fas fa-check" x-show="isSelected(opt.id)"></i></span>
						<span x-text="opt.text"></span>
					</li>
				</template>
				<li class="arch-combobox-empty" x-show="!loading && options.length === 0 && query">
					{{ __('No results found.') }}
				</li>
			</ul>
		</div>
	</div>
	@error('form.'.$fieldName)
		<div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
	@enderror
</div>
