{{--
    Architect Supersearch — engine view.

    Renders a full-screen modal overlay with a search input and grouped results.
    Alpine handles keyboard navigation, debounce, clipboard, and focus trap.
    Livewire handles the actual search execution and action dispatch.

    Hook cleanup:
    When a hooked Architect engine (table, spa-tabs) is removed from the DOM
    (e.g. SPA navigation), its x-init $cleanup callback dispatches a window
    event that Alpine catches here and calls $wire.onHookUnmounted(componentId).
--}}
<div
    x-data="architectSupersearch({
        key:          '{{ $definition->key }}',
        shortcut:     '{{ $definition->shortcut }}',
        placeholder:  '{{ $definition->placeholder }}',
    })"
    x-init="init()"
    @architect:supersearch:open.window="openOverlay()"
    @architect:supersearch:hook-unmounted.window="$wire.onHookUnmounted($event.detail.componentId)"
    @keydown.escape.window="closeOverlay()"
>
    {{-- Overlay --}}
    <template x-if="open">
        <div
            class="fixed inset-0 z-[9000] overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-label="Search"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
                @click="closeOverlay()"
                aria-hidden="true"
            ></div>

            {{-- Dialog panel --}}
            <div class="relative mx-auto mt-16 max-w-2xl rounded-xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/5 dark:ring-white/10 overflow-hidden">

                {{-- Search input row — wire:ignore prevents Livewire's morph from touching
                     this element, which would otherwise steal focus mid-search. Alpine
                     owns the value via x-model so Livewire never needs to diff it. --}}
                <div class="flex items-center px-4 border-b border-gray-200 dark:border-gray-700" wire:ignore>
                    <i class="fas fa-search text-gray-400 dark:text-gray-500 text-sm shrink-0"></i>
                    <input
                        x-ref="input"
                        type="text"
                        x-model="query"
                        @input="onInput()"
                        @keydown.arrow-up.prevent="navigateUp()"
                        @keydown.arrow-down.prevent="navigateDown()"
                        @keydown.enter.prevent="selectActive()"
                        class="flex-1 bg-transparent px-3 py-4 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 outline-none"
                        :placeholder="placeholder"
                        autocomplete="off"
                        spellcheck="false"
                    />
                    <button
                        type="button"
                        @click="closeOverlay()"
                        class="shrink-0 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 text-xs px-1"
                        title="Close (Esc)"
                    >
                        <kbd class="inline-flex items-center rounded border border-gray-300 dark:border-gray-600 px-1 py-0.5 font-mono text-[10px]">Esc</kbd>
                    </button>
                </div>

                {{-- Results body --}}
                <div class="max-h-[30rem] overflow-y-auto overscroll-contain divide-y divide-gray-100 dark:divide-gray-800" x-ref="resultsList" data-loading="{{ $isLoading ? 'true' : 'false' }}">

                    @if ($hasError)
                        <x-architect::callout type="danger" class="m-3">{{ $errorMessage }}</x-architect::callout>
                    @endif

                    {{-- Loading indicator --}}
                    <div wire:loading wire:target="search" class="flex items-center justify-center py-8">
                        <i class="fas fa-circle-notch fa-spin text-gray-400 dark:text-gray-500"></i>
                    </div>

                    {{-- Results --}}
                    <div wire:loading.remove wire:target="search">
                        @if(count($this->results) > 0)
                            @php $flatIndex = 0; @endphp
                            @foreach($this->results as $groupIndex => $group)
                                @include('architect::supersearch.partials.group-header', ['label' => $group['groupLabel']])
                                @foreach($group['items'] as $itemIndex => $item)
                                    @include('architect::supersearch.partials.result', [
                                        'item'        => $item,
                                        'groupIndex'  => $groupIndex,
                                        'itemIndex'   => $itemIndex,
                                        'flatIndex'   => $flatIndex,
                                    ])
                                    @php $flatIndex++; @endphp
                                @endforeach
                            @endforeach

                        @elseif(strlen($this->lastQuery) >= 2)
                            @include('architect::supersearch.partials.empty-state')

                        @else
                            @include('architect::supersearch.partials.recent-searches')

                        @endif
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center gap-x-3 border-t border-gray-100 dark:border-gray-800 px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
                    <span class="flex items-center gap-1">
                        <kbd class="inline-flex items-center rounded border border-gray-200 dark:border-gray-700 px-1 font-mono text-[10px]">↑↓</kbd>
                        Navigate
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="inline-flex items-center rounded border border-gray-200 dark:border-gray-700 px-1 font-mono text-[10px]">↵</kbd>
                        Select
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="inline-flex items-center rounded border border-gray-200 dark:border-gray-700 px-1 font-mono text-[10px]">Esc</kbd>
                        Close
                    </span>
                </div>

            </div>
        </div>
    </template>

    {{-- Clipboard toast (shown after copy action) --}}
    <template x-if="copiedToast">
        <div
            class="fixed bottom-4 right-4 z-[9100] flex items-center gap-2 rounded-lg bg-gray-900 dark:bg-white px-4 py-2 text-sm text-white dark:text-gray-900 shadow-lg"
            x-transition
        >
            <i class="fas fa-check text-green-400 dark:text-green-600"></i>
            Copied to clipboard
        </div>
    </template>
</div>
