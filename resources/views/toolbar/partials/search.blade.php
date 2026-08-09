{{--
    Toolbar partial: ToolbarSearch — a search input that lives directly in the toolbar bar.

    Two modes:
      simple  — debounced input → $wire.setSearch(key, value) → browser event
      suggest — debounced input → $wire.requestSuggestions(key, query)
                Parent responds via architect:toolbar:search-suggestions
                → $searchSuggestions[key] populated → flyout shown

    Alpine local state:
      searchVal       — mirrors $wire.searchValues[key], updated via $watch for
                        reactivity on Livewire-initiated changes (e.g. selectSuggestion)
      suggestOpen     — whether the suggestion flyout should show (focus tracking)
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\ToolbarSearch $item */
    if (!$this->can($item->getPermission())) return;

    $searchKey    = $item->getKey();
    $currentValue = $searchValues[$searchKey] ?? '';
    $suggestions  = $searchSuggestions[$searchKey] ?? [];
    $isLoading    = $searchLoading[$searchKey] ?? false;
    $alpineId     = 'search_' . preg_replace('/[^a-z0-9_]/', '_', $searchKey);
    $persist      = $item->getPersist();
    $lsKey        = $persist === 'local'
        ? "architectToolbar_{$toolbarKey}_search_{$searchKey}"
        : null;
@endphp

<div
    x-data="{
        {{ $alpineId }}_val:  '{{ addslashes($currentValue) }}',
        {{ $alpineId }}_open: false,
    }"
    x-init="
        $watch(
            () => ($wire.searchValues?.['{{ $searchKey }}'] ?? ''),
            v  => { {{ $alpineId }}_val = v; }
        );
        @if ($lsKey)
            const __stored = localStorage.getItem('{{ $lsKey }}');
            if (__stored !== null && __stored !== '') $wire.call('setSearch', '{{ $searchKey }}', __stored);
        @endif
    "
    class="relative flex-shrink-0 {{ $item->getWidth() }}"
>
    <div class="relative flex items-center">
        {{-- Left icon --}}
        @if ($item->getIcon())
            <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 z-10 text-gray-400">
                <i class="{{ $item->getIcon() }} text-xs"></i>
            </span>
        @endif

        {{-- Search input --}}
        <input
            type="search"
            autocomplete="off"
            :value="{{ $alpineId }}_val"
            placeholder="{{ $item->getPlaceholder() ?? $item->getLabel() }}"
            @if ($item->isDisabled()) disabled @endif
            @focus="{{ $alpineId }}_open = true"
            @blur.debounce.200ms="{{ $alpineId }}_open = false"
            @if ($item->isSuggestMode())
                x-on:input.debounce.{{ $item->getDebounceMs() }}ms="
                    {{ $alpineId }}_val = $el.value;
                    $wire.call('requestSuggestions', '{{ $searchKey }}', $el.value);
                "
            @else
                x-on:input.debounce.{{ $item->getDebounceMs() }}ms="
                    {{ $alpineId }}_val = $el.value;
                    $wire.call('setSearch', '{{ $searchKey }}', $el.value);
                "
            @endif
            @class([
                'block w-full rounded-md border text-sm py-1.5 transition',
                'pl-8' => $item->getIcon() !== '',
                'pl-3' => $item->getIcon() === '',
                'pr-8' => $item->isClearable(),
                'pr-3' => !$item->isClearable(),
                'border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
                'dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-primary-400',
                'opacity-50 cursor-not-allowed' => $item->isDisabled(),
            ])
        >

        {{-- Clear button --}}
        @if ($item->isClearable())
            <button
                type="button"
                x-show="{{ $alpineId }}_val !== ''"
                wire:click="clearSearch('{{ $searchKey }}')"
                @click="{{ $alpineId }}_val = ''"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                aria-label="Clear search"
                tabindex="-1"
            >
                <i class="fas fa-times text-xs"></i>
            </button>
        @endif
    </div>

    {{-- Suggestion flyout (suggest mode only) --}}
    @if ($item->isSuggestMode())
        <ul
            x-show="{{ $alpineId }}_open && (
                ($wire.searchLoading?.['{{ $searchKey }}'] ?? false) ||
                (($wire.searchSuggestions?.['{{ $searchKey }}']?.length ?? 0) > 0)
            )"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            role="listbox"
            aria-label="{{ $item->getPlaceholder() ?? $item->getLabel() }} suggestions"
            class="absolute z-30 mt-1 w-full min-w-[16rem] rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            {{-- Loading spinner (shown by Livewire while requestSuggestions is in flight) --}}
            @if ($isLoading)
                <li class="flex items-center gap-2 px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-circle-notch fa-spin text-primary-500"></i>
                    <span>Searching…</span>
                </li>
            @else
                @forelse ($suggestions as $suggestion)
                    <li role="option">
                        <button
                            type="button"
                            wire:click="selectSuggestion('{{ $searchKey }}', '{{ addslashes($suggestion['value']) }}', '{{ addslashes($suggestion['label']) }}')"
                            @mousedown.prevent
                            @click="{{ $alpineId }}_open = false"
                            class="flex w-full items-start gap-3 px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 text-left"
                        >
                            @if (!empty($suggestion['icon']))
                                <i class="{{ $suggestion['icon'] }} mt-0.5 w-4 shrink-0 text-center text-gray-400"></i>
                            @endif
                            <span class="flex-1 min-w-0">
                                <span class="block truncate text-gray-900 dark:text-gray-100">{{ $suggestion['label'] }}</span>
                                @if (!empty($suggestion['sublabel']))
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $suggestion['sublabel'] }}</span>
                                @endif
                            </span>
                        </button>
                    </li>
                @empty
                    {{-- No results row — only shows when we had a query but got nothing back --}}
                    @if (($searchValues[$searchKey] ?? '') !== '')
                        <li class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500 italic">No results found.</li>
                    @endif
                @endforelse
            @endif
        </ul>
    @endif
</div>
