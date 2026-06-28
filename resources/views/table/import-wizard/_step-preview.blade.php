{{-- Step 2: Preview with inline editing --}}
@php
    /** @var array<string, \Entelechy\Architect\Table\Column> $importColumns */

    // Build edit-key -> display-key lookup so cascadeFrom values can
    // read the sibling parent cell from parsed row data.
    $editToDisplayMap = [];
    foreach ($importColumns as $dispKey => $colDef) {
        $editToDisplayMap[$colDef->getEditKey() ?? $dispKey] = $dispKey;
    }
@endphp

{{-- Status bar --}}
<div class="flex flex-wrap gap-3 items-center mb-3 p-2 border rounded-md bg-gray-100 dark:bg-gray-700/50">
    <span class="text-sm text-gray-700 dark:text-gray-200">
        <i class="fas fa-check ml-1 text-green-600 dark:text-green-400"></i>{{ $validCount }} valid
    </span>
    <span class="text-sm text-gray-700 dark:text-gray-200">
        <i class="fas fa-times ml-1 text-red-600 dark:text-red-400"></i>{{ $invalidCount }} invalid
    </span>
    @if ($duplicateCount > 0)
        <span class="text-sm text-gray-700 dark:text-gray-200">
            <i class="fas fa-clone ml-1"></i>{{ $duplicateCount }} duplicate {{ $duplicateCount === 1 ? '' : 's' }}
        </span>
        <div class="arch-check arch-switch ml-auto mb-0">
            <input class="arch-check-input" type="checkbox" id="skip-dupes" wire:model.live="skipDuplicates">
            <label class="arch-check-label text-sm" for="skip-dupes">Skip duplicates on import</label>
        </div>
    @endif
</div>

@if ($invalidCount > 0)
    <div class="arch-alert arch-alert-warning text-sm">
        <i class="fas fa-info-circle ml-1"></i>
        Edit the highlighted cells below. Unselected rows are skipped. Continue enables once every selected row is valid.
    </div>
@endif

{{-- Preview table --}}
<div class="table-responsive" style="max-height: 50vh;">
    <table class="arch-table arch-table-sm arch-table-bordered">
        <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0 z-10">
            <tr>
                <th style="width: 72px;">
                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            class="arch-check-input"
                            wire:click="setAllRowSelections($event.target.checked)"
                            @checked($this->allSelectableRowsSelected)
                            @disabled(!$this->hasSelectableRows)
                            aria-label="Select or unselect all rows"
                        >
                        <span>#</span>
                    </div>
                </th>
                @foreach ($importColumns as $key => $col)
                    <th>{{ $col->getLabel() }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($parsedRows as $idx => $row)
                @php
                    $isIgnored = (bool) ($row['ignored'] ?? false);
                    $isExample = (bool) ($row['is_example'] ?? false);
                    $hasError = ($row['errors'] ?? []) !== [];
                    $isDup = $row['duplicate'] ?? false;
                    $rowClass = $isIgnored
                        ? 'table-danger'
                        : ($hasError ? 'table-danger' : ($isDup ? 'table-warning' : 'table-success'));
                @endphp
                <tr class="{{ $rowClass }}" wire:key="row-{{ $idx }}">
                    <td class="text-gray-500 dark:text-gray-400">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                class="arch-check-input"
                                wire:click="toggleIgnoreRow({{ $idx }})"
                                @checked(!$isIgnored)
                                @disabled($isExample)
                                aria-label="Select row {{ $idx + 1 }} for import"
                            >
                            <span>{{ $idx + 1 }}</span>
                        </div>
                    </td>

                    @foreach ($importColumns as $key => $col)
                        @php
                            $cellErrors = $row['errors'][$key] ?? [];
                            $cellValue = $row['values'][$key] ?? '';
                            // Use the column-defined input type; default to text.
                            $cellType = $col->getType() ?? 'text';
                            $ruleString = strtolower((string) ($col->getRules() ?? ''));
                            $isBooleanRule = str_contains('|' . $ruleString . '|', '|boolean|');
                            $effectiveType = $isBooleanRule ? 'checkbox' : $cellType;
                            $invalidCls = $cellErrors ? 'is-invalid' : '';
                            $wireModel = "parsedRows.{$idx}.values.{$key}";
                        @endphp
                        <td>
                            @if ($hasError && ! $isExample)
                                @switch($effectiveType)
                                    @case('checkbox')
                                        <div class="arch-check arch-switch m-0">
                                            <input
                                                type="checkbox"
                                                class="arch-check-input {{ $invalidCls }}"
                                                @checked(filter_var($cellValue, FILTER_VALIDATE_BOOLEAN))
                                                wire:change="updateBooleanCell({{ $idx }}, '{{ $key }}', $event.target.checked)"
                                            >
                                        </div>
                                        @break

                                    @case('select')
                                        <select
                                            class="arch-select arch-select-sm {{ $invalidCls }}"
                                            wire:model.live="{{ $wireModel }}"
                                        >
                                            <option value="">—</option>
                                            @foreach (($col->getOptions() ?? []) as $optValue => $optLabel)
                                                <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('multiselect')
                                        <select
                                            multiple
                                            class="arch-select arch-select-sm {{ $invalidCls }}"
                                            wire:model.live="{{ $wireModel }}"
                                            size="3"
                                        >
                                            @foreach (($col->getOptions() ?? []) as $optValue => $optLabel)
                                                <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('lookup')
                                        @php
                                            $src = $col->getSource()
                                                ?? \Entelechy\Architect\Table\Http\LookupController::urlFor($definitionClass, $col->key());
                                            $cascadeEditKey = $col->getCascadeFrom();
                                            $cascadeDisplayKey = $cascadeEditKey
                                                ? ($editToDisplayMap[$cascadeEditKey] ?? $cascadeEditKey)
                                                : null;
                                            $parentValue = $cascadeDisplayKey
                                                ? ($row['values'][$cascadeDisplayKey] ?? '')
                                                : '';
                                            if ($cascadeEditKey !== null && preg_match('/^\d+$/', (string) $parentValue)) {
                                                $src .= (str_contains($src, '?') ? '&' : '?')
                                                    . rawurlencode($cascadeEditKey)
                                                    . '='
                                                    . rawurlencode((string) $parentValue);
                                            }
                                            $selectId = "import-combobox-{$idx}-{$key}";
                                            $selectWireKey = sprintf(
                                                'import-combobox-%d-%s-%s',
                                                $idx,
                                                $key,
                                                md5($src . '|' . (string) $cellValue . '|' . (string) ($row['display_values'][$key] ?? '')),
                                            );
                                            $placeholderText = 'Select ' . strtolower($col->getLabel()) . '...';
                                            $selectedNumeric = preg_match('/^\d+$/', (string) $cellValue)
                                                ? (string) $cellValue
                                                : null;
                                        @endphp
                                        <div
                                            id="{{ $selectId }}"
                                            wire:key="{{ $selectWireKey }}"
                                            class="arch-combobox"
                                            x-data="architectCombobox({
                                                url: @js($src),
                                                value: {{ $selectedNumeric !== null ? json_encode($selectedNumeric) : 'null' }},
                                                placeholder: @js($placeholderText),
                                                onChange: function(v) {
                                                    if (v === null || v === '') {
                                                        $wire.call('updateLookupCell', {{ $idx }}, @js($key), '');
                                                    } else {
                                                        $wire.call('updateLookupCell', {{ $idx }}, @js($key), String(v));
                                                    }
                                                },
                                            })"
                                            x-effect="const currentValue = $wire.parsedRows[{{ $idx }}]?.values?.[{{ json_encode($key) }}] ?? null; syncValue(currentValue); const currentLabel = $wire.parsedRows[{{ $idx }}]?.display_values?.[{{ json_encode($key) }}] ?? null; if (currentValue !== null && currentValue !== '' && currentLabel) { _labels[String(currentValue)] = currentLabel; }"
                                            @click.outside="closeDropdown()"
                                            @keydown="onKeydown($event)"
                                        >
                                            <button
                                                type="button"
                                                class="arch-combobox-trigger {{ $invalidCls }}"
                                                :class="{ open }"
                                                @click="toggleDropdown()"
                                                :aria-expanded="open"
                                                aria-haspopup="listbox"
                                            >
                                                <span class="arch-combobox-value" x-show="hasValue" x-text="selectedLabel"></span>
                                                <span class="arch-combobox-placeholder" x-show="!hasValue">
                                                    @if ($cellValue !== '' && ! preg_match('/^\d+$/', (string) $cellValue))
                                                        {{ $cellValue }} (unmatched)
                                                    @else
                                                        {{ $placeholderText }}
                                                    @endif
                                                </span>
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
                                                        placeholder="Search..."
                                                        autocomplete="off"
                                                    >
                                                </div>
                                                <ul x-ref="optionList" class="arch-combobox-options">
                                                    <li class="arch-combobox-loading" x-show="loading">
                                                        <i class="fas fa-circle-notch fa-spin text-gray-400"></i>
                                                        Loading...
                                                    </li>
                                                    <template x-for="(opt, optionIdx) in options" :key="opt.id">
                                                        <li
                                                            class="arch-combobox-option"
                                                            :class="{ active: activeIdx === optionIdx, selected: isSelected(opt.id) }"
                                                            @click="select(opt.id, opt.text)"
                                                            :aria-selected="isSelected(opt.id)"
                                                            role="option"
                                                        >
                                                            <span class="arch-combobox-check"><i class="fas fa-check" x-show="isSelected(opt.id)"></i></span>
                                                            <span x-text="opt.text"></span>
                                                        </li>
                                                    </template>
                                                    <li class="arch-combobox-empty" x-show="!loading && options.length === 0 && query">
                                                        No results found.
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        @if ($cellValue !== '' && ! preg_match('/^\d+$/', (string) $cellValue))
                                            <div class="block text-sm text-danger-600 dark:text-danger-400">No matching record found for this value.</div>
                                        @endif
                                        @break

                                    @case('textarea')
                                        <textarea
                                            class="arch-input arch-input-sm {{ $invalidCls }}"
                                            wire:model.live.debounce.500ms="{{ $wireModel }}"
                                            rows="2"
                                            placeholder="{{ $col->getPlaceholder() }}"
                                        >{{ $cellValue }}</textarea>
                                        @break

                                    @case('date')
                                    @case('datetime')
                                    @case('date_time')
                                    @case('time')
                                    @case('number')
                                    @case('color')
                                        @php
                                            $nativeType = match ($cellType) {
                                                'number' => 'number',
                                                'color' => 'color',
                                                default => 'text',
                                            };
                                        @endphp
                                        <input
                                            type="{{ $nativeType }}"
                                            class="arch-input arch-input-sm {{ $invalidCls }}"
                                            wire:model.live.debounce.500ms="{{ $wireModel }}"
                                            placeholder="{{ $col->getPlaceholder() }}"
                                        >
                                        @break

                                    @default
                                        <input
                                            type="text"
                                            class="arch-input arch-input-sm {{ $invalidCls }}"
                                            wire:model.live.debounce.500ms="{{ $wireModel }}"
                                            placeholder="{{ $col->getPlaceholder() }}"
                                        >
                                @endswitch

                                @foreach ($cellErrors as $err)
                                    <div class="block text-sm text-danger-600 dark:text-danger-400">{{ $err }}</div>
                                @endforeach
                            @else
                                @if ($effectiveType === 'checkbox')
                                    @if (filter_var($cellValue, FILTER_VALIDATE_BOOLEAN))
                                        <i class="fas fa-check text-green-600 dark:text-green-400"></i>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                @elseif ($effectiveType === 'select' && $col->getOptions())
                                    <span class="text-sm">{{ $col->getOptions()[$cellValue] ?? $cellValue }}</span>
                                @elseif ($effectiveType === 'lookup')
                                    {{-- Show the human-readable label. The resolver replaces the
                                         original text with the resolved FK id, but also stores the
                                         label in display_values so we can surface it here. --}}
                                    <span class="text-sm">{{ $row['display_values'][$key] ?? $cellValue }}</span>
                                @else
                                    <span class="text-sm">{{ $cellValue }}</span>
                                @endif
                            @endif
                        </td>
                    @endforeach

                </tr>
            @endforeach
        </tbody>
    </table>
</div>
