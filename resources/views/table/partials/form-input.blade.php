{{--
    ModuleTable form input partial - renders form inputs based on column type.

    Variables:
    - $column: Column instance
    - $editKey: The key to use for form binding (from $column->getEditKey())
    - $value: Current value from form array
    - $type: Column type (text, select, checkbox, etc.)
    - $hasError: Boolean indicating if this field has validation errors
--}}

@php
    $canEdit = $canEdit ?? true;
@endphp

@if ($type === 'checkbox')
    {{-- Checkbox fields are rendered as switches for parity with table toggles. --}}
    @php
        $switchId = 'form-switch-' . str_replace(['.', '[', ']'], '-', (string) $editKey);
    @endphp
    <div class="flex flex-col gap-2">
        <div class="arch-check arch-switch mb-0 inline-flex items-center gap-3">
            <input
                type="checkbox"
                role="switch"
                id="{{ $switchId }}"
                class="arch-switch-input"
                wire:model="form.{{ $editKey }}"
                value="1"
                @disabled(! $canEdit)
            >
            <label class="arch-check-label text-sm font-medium text-gray-700 dark:text-gray-200 mb-0" for="{{ $switchId }}">
                {{ $column->getLabel() }}
            </label>
        </div>
    </div>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@else
    {{-- All other field types have standard label --}}
    <label class="text-sm font-medium text-gray-700 dark:text-gray-200 {{ $column->getRules() && str_contains($column->getRules(), 'required') ? 'required' : '' }}">
        {{ $column->getLabel() }}
    </label>

    @if (! $canEdit && $type !== 'hidden')
        @php
            $display = $value;
            if (is_array($value)) {
                $display = $value['txt'] ?? ($value['val'] ?? json_encode($value));
            } elseif (is_bool($value)) {
                $display = $value ? 'Yes' : 'No';
            }
        @endphp
        <div class="arch-input bg-gray-50 dark:bg-gray-900/40 text-gray-600 dark:text-gray-300">
            {{ $display === null || $display === '' ? '—' : $display }}
        </div>
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Read-only for your permission level</div>

    @elseif ($type === 'hidden')
        <input type="hidden" wire:model="form.{{ $editKey }}">

    @elseif ($type === 'textarea')
        <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
            <textarea
                class="arch-input"
                wire:model="form.{{ $editKey }}"
                rows="4"
                placeholder="Enter {{ strtolower($column->getLabel()) }}"
            ></textarea>
        </x-architect::input-wrapper>
        @error('form.'.$editKey)
            <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror

    @elseif ($type === 'select')
    {{-- Standard select dropdown --}}
    <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
        <select
            class="arch-select"
            wire:model="form.{{ $editKey }}"
        >
            <option value="">Select {{ strtolower($column->getLabel()) }}...</option>
            @if ($column->getOptions())
                @foreach ($column->getOptions() as $optValue => $optLabel)
                    <option value="{{ $optValue }}">{{ $optLabel }}</option>
                @endforeach
            @endif
        </select>
    </x-architect::input-wrapper>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'lookup')
    {{-- AJAX combobox lookup field. --}}
    @php
        $lookupBaseSource = $column->getSource()
            ?? \Entelechy\Architect\Table\Http\LookupController::urlFor($definitionClass, $column->key());
        $lookupPlaceholder = 'Select ' . strtolower($column->getLabel()) . '...';
        $lookupCurrent = is_array($value) ? ($value['val'] ?? null) : $value;
        $lookupCurrentText = is_array($value)
            ? (string) ($value['txt'] ?? '')
            : (! ctype_digit(trim((string) $value)) ? (string) $value : '');
        $_cascadeFrom = $column->getCascadeFrom();
        $_extraFrom = $column->getExtraDataFrom();
        $lookupId = 'form-combobox-' . str_replace(['.', '[', ']'], '-', (string) $editKey);
    @endphp
    <div
        id="{{ $lookupId }}"
        class="arch-combobox"
        x-data="architectCombobox({
            url: @js($lookupBaseSource),
            value: {{ $lookupCurrent !== null && $lookupCurrent !== '' ? json_encode((string) $lookupCurrent) : 'null' }},
            placeholder: @js($lookupPlaceholder),
            onChange: function(v) {
                if (v === null || v === '') {
                    $wire.set('form.{{ $editKey }}', null);
                } else {
                    $wire.set('form.{{ $editKey }}', { val: String(v), txt: this._labels[String(v)] ?? String(v) });
                }
            },
        })"
        x-effect="
            const currentValue = $wire.form?.[{{ json_encode($editKey) }}]?.val ?? $wire.form?.[{{ json_encode($editKey) }}] ?? null;
            const currentLabel = $wire.form?.[{{ json_encode($editKey) }}]?.txt ?? null;
            syncValue(currentValue);
            if (currentValue !== null && currentValue !== '' && currentLabel) {
                _labels[String(currentValue)] = currentLabel;
            }
            let nextUrl = {{ json_encode($lookupBaseSource) }};
            @if ($_cascadeFrom)
                const cascadeValue = $wire.form?.[{{ json_encode($_cascadeFrom) }}]?.val ?? $wire.form?.[{{ json_encode($_cascadeFrom) }}] ?? null;
                if (cascadeValue !== null && cascadeValue !== '' && /^\d+$/.test(String(cascadeValue))) {
                    nextUrl += (nextUrl.includes('?') ? '&' : '?') + {{ json_encode($_cascadeFrom) }} + '=' + encodeURIComponent(String(cascadeValue));
                }
            @endif
            @if ($_extraFrom)
                const extraValue = $wire.form?.[{{ json_encode($_extraFrom) }}]?.val ?? $wire.form?.[{{ json_encode($_extraFrom) }}] ?? null;
                if (extraValue !== null && extraValue !== '') {
                    nextUrl += (nextUrl.includes('?') ? '&' : '?') + 'extra=' + encodeURIComponent(String(extraValue));
                }
            @endif
            if (_url !== nextUrl) {
                _url = nextUrl;
                if (open) {
                    _fetchOptions(query);
                }
            }
        "
        @click.outside="closeDropdown()"
        @keydown="onKeydown($event)"
    >
        <button
            type="button"
            class="arch-combobox-trigger @error('form.'.$editKey) border-red-400 @enderror"
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
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'multiselect')
    {{-- Multi-select --}}
    <select
        multiple
        class="arch-select"
        wire:model="form.{{ $editKey }}"
        size="5"
    >
        @if ($column->getOptions())
            @foreach ($column->getOptions() as $optValue => $optLabel)
                <option value="{{ $optValue }}">{{ $optLabel }}</option>
            @endforeach
        @endif
    </select>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'date')
    {{-- Date picker --}}
    <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
        <input
            type="date"
            class="arch-input"
            wire:model="form.{{ $editKey }}"
            placeholder="dd/mm/yyyy"
        >
    </x-architect::input-wrapper>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'datetime' || $type === 'date_time')
    {{-- Datetime picker --}}
    <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
        <input
            type="datetime-local"
            class="arch-input"
            wire:model="form.{{ $editKey }}"
        >
    </x-architect::input-wrapper>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'time')
    {{-- Time picker --}}
    <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
        <input
            type="time"
            class="arch-input"
            wire:model="form.{{ $editKey }}"
        >
    </x-architect::input-wrapper>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'number')
    {{-- Number input --}}
    <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
        <input
            type="number"
            class="arch-input"
            wire:model="form.{{ $editKey }}"
            placeholder="Enter {{ strtolower($column->getLabel()) }}"
        >
    </x-architect::input-wrapper>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'color' || $type === 'color-picker')
    {{-- Color picker --}}
    <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
        <input
            type="color"
            class="arch-input"
            wire:model="form.{{ $editKey }}"
            title="Choose {{ strtolower($column->getLabel()) }}"
        >
    </x-architect::input-wrapper>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'wysiwyg')
    {{-- WYSIWYG editor (simplified - could be enhanced with TinyMCE/CKEditor) --}}
    <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
        <textarea
            class="arch-input"
            wire:model="form.{{ $editKey }}"
            rows="8"
            placeholder="Enter {{ strtolower($column->getLabel()) }}"
        ></textarea>
    </x-architect::input-wrapper>
    <div class="fi-fo-hint text-sm text-gray-500 dark:text-gray-400">Rich text editor (basic mode)</div>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'upload' || $type === 'awsUpload')
    {{-- File upload --}}
    @php
        $_currentUpload = is_string($value) && $value !== '' ? $value : null;
    @endphp
    @if ($_currentUpload)
        <div class="mb-2 flex items-center gap-3">
            @if (str_starts_with(\Illuminate\Support\Facades\Storage::disk($column->getDisk())->mimeType($_currentUpload) ?? '', 'image/'))
                <img src="{{ \Illuminate\Support\Facades\Storage::disk($column->getDisk())->url($_currentUpload) }}" alt="" class="h-12 w-12 rounded object-cover border border-gray-200 dark:border-gray-700">
            @endif
            <a href="{{ \Illuminate\Support\Facades\Storage::disk($column->getDisk())->url($_currentUpload) }}" target="_blank" class="text-xs text-blue-600 hover:underline dark:text-blue-400">View current file</a>
        </div>
    @endif
    <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
        <input
            type="file"
            class="arch-input"
            wire:model="form.{{ $editKey }}"
        >
    </x-architect::input-wrapper>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

@elseif ($type === 'duallistbox')
    {{-- Two-pane multi-select.
         Source can be either static ->options() or AJAX ->source()
         (or the auto lookup endpoint via the column's exists rule).
         Selected values bridge to wire:model via @this.set on every change. --}}
    @php
        $_dualOptions = $column->getOptions() ?? [];
        $_dualUrl = null;
        if ($_dualOptions === []) {
            $_dualUrl = $column->getSource()
                ?? \Entelechy\Architect\Table\Http\LookupController::urlFor($definitionClass, $column->key());
        }
        $_dualSelected = is_array($value) ? array_values($value) : [];
    @endphp
    <div
        x-data="{
            available: [],
            selected: @js($_dualSelected),
            loaded: false,
            async load() {
                if (this.loaded) return;
                @if ($_dualUrl)
                    try {
                        const res = await fetch('{{ $_dualUrl }}?autoload=1&limit=500', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        // Flatten optgroups if present.
                        const flat = [];
                        (data || []).forEach(item => {
                            if (item.children) item.children.forEach(c => flat.push(c));
                            else flat.push(item);
                        });
                        this.available = flat;
                    } catch (e) {
                        console.error('duallistbox load failed', e);
                    }
                @else
                    this.available = @js(collect($_dualOptions)->map(fn ($label, $id) => ['id' => (string) $id, 'text' => (string) $label])->values()->all());
                @endif
                this.loaded = true;
                this.sync();
            },
            isSelected(id) { return this.selected.map(String).includes(String(id)); },
            move(id) {
                const sid = String(id);
                if (this.isSelected(sid)) {
                    this.selected = this.selected.filter(s => String(s) !== sid);
                } else {
                    this.selected = [...this.selected, sid];
                }
                this.sync();
            },
            addAll() {
                this.selected = this.available.map(o => String(o.id));
                this.sync();
            },
            removeAll() {
                this.selected = [];
                this.sync();
            },
            sync() {
                @this.set('form.{{ $editKey }}', this.selected);
            }
        }"
        x-init="load()"
        class="grid grid-cols-12 gap-2"
    >
        <div class="col-span-5">
            <label class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Available</label>
            <ul class="flex flex-col border border-gray-200 dark:border-gray-700 rounded divide-y divide-gray-100 dark:divide-gray-700" style="max-height: 240px; overflow-y: auto;">
                <template x-for="opt in available.filter(o => !isSelected(o.id))" :key="opt.id">
                    <li class="px-2 py-1 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                        @click="move(opt.id)" x-text="opt.text"></li>
                </template>
            </ul>
        </div>
        <div class="col-span-2 flex flex-col justify-center items-center gap-1">
            <x-architect::button size="sm" color="gray" outlined class="w-full" @click="addAll()" title="Add all">
                <i class="fas fa-angle-double-right"></i>
            </x-architect::button>
            <x-architect::button size="sm" color="gray" outlined class="w-full" @click="removeAll()" title="Remove all">
                <i class="fas fa-angle-double-left"></i>
            </x-architect::button>
        </div>
        <div class="col-span-5">
            <label class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                Selected (<span x-text="selected.length"></span>)
            </label>
            <ul class="flex flex-col border border-gray-200 dark:border-gray-700 rounded divide-y divide-gray-100 dark:divide-gray-700" style="max-height: 240px; overflow-y: auto;">
                <template x-for="opt in available.filter(o => isSelected(o.id))" :key="opt.id">
                    <li class="px-2 py-1 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                        @click="move(opt.id)" x-text="opt.text"></li>
                </template>
            </ul>
        </div>
    </div>
    @error('form.'.$editKey)
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror

    @else
        {{-- Default: text input --}}
        <x-architect::input-wrapper :valid="! $errors->has('form.' . $editKey)">
            <input
                type="text"
                class="arch-input"
                wire:model="form.{{ $editKey }}"
                placeholder="Enter {{ strtolower($column->getLabel()) }}"
            >
        </x-architect::input-wrapper>
        @error('form.'.$editKey)
            <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
        @enderror
    @endif
@endif
