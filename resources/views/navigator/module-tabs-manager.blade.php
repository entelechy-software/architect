{{--
    ModuleTabsManager — full-width in-page tab workspace.

    This view renders:
      1. The tab bar (labels, icons, close buttons, overflow popover, actions)
      2. All content panels simultaneously — Alpine x-show handles visibility

    Alpine store: $store.moduleTabs
    Key Livewire methods: openTab, closeTab, restoreTabs, markMounted
--}}
@if ($hasError || $definition === null)
    <x-architect::callout type="danger">{{ $errorMessage }}</x-architect::callout>
@else
<div
    class="arch-module-tabs"
    data-loading="{{ $isLoading ? 'true' : 'false' }}"
    x-data
    x-init="$store.moduleTabs.setup($wire, @js($definition->toClientConfig()))"
    @module-tabs:switch.window="$store.moduleTabs.switch($event.detail.tabId)"
    @module-tabs:persist.window="$store.moduleTabs.persist($event.detail.openTabs)"
    @architect:tab-dirty.window="$store.moduleTabs.markDirty($event.detail.tabId, $event.detail.dirty)"
    @architect:tab-saved.window="$store.moduleTabs.markSaved($event.detail.tabId)"
>
    {{-- ── Tab bar ──────────────────────────────────────────────────── --}}
    <div class="arch-module-tabs__bar" x-ref="tabBar">

        {{-- Tab items --}}
        <div class="arch-module-tabs__tabs" x-ref="tabList">
            @foreach ($openTabs as $tab)
                @php $isStale = $staleTabs[$tab['id']] ?? false; @endphp

                @if (!empty($tab['dropdown_items']))
                    {{-- Pinned tab with filter dropdown: two side-by-side buttons inside a wrapper --}}
                    <div
                        class="arch-module-tabs__tab-group"
                        x-data="{ dropOpen: false, activeLabel: '' }"
                        @click.outside="dropOpen = false"
                    >
                        {{-- Main activate button --}}
                        <button
                            type="button"
                            class="arch-module-tabs__tab arch-module-tabs__tab--has-dropdown group"
                            :class="{
                                'arch-module-tabs__tab--active':  $store.moduleTabs.activeId === '{{ $tab['id'] }}',
                                'arch-module-tabs__tab--dirty':   $store.moduleTabs.isDirty('{{ $tab['id'] }}'),
                                'arch-module-tabs__tab--saved':   $store.moduleTabs.isSaved('{{ $tab['id'] }}'),
                                'arch-module-tabs__tab--stale':   {{ $isStale ? 'true' : 'false' }},
                                'arch-module-tabs__tab--pinned':  true,
                            }"
                            @click="$store.moduleTabs.switchTo('{{ $tab['id'] }}', $wire)"
                            @contextmenu.prevent="$store.moduleTabs.openContextMenu($event, '{{ $tab['id'] }}')"
                            :tabindex="$store.moduleTabs.activeId === '{{ $tab['id'] }}' ? 0 : -1"
                            title="{{ $tab['label'] }}"
                        >
                            @if ($tab['icon'])
                                <i class="{{ $tab['icon'] }} arch-module-tabs__tab-icon" aria-hidden="true"></i>
                            @endif
                            <span class="arch-module-tabs__tab-label" x-text="activeLabel || '{{ $tab['label'] }}'"></span>
                        </button>

                        {{-- Dropdown chevron toggle --}}
                        <button
                            type="button"
                            class="arch-module-tabs__tab-dropdown-toggle"
                            :class="{ 'arch-module-tabs__tab--active': $store.moduleTabs.activeId === '{{ $tab['id'] }}' }"
                            @click.stop="$store.moduleTabs.switchTo('{{ $tab['id'] }}', $wire); dropOpen = !dropOpen"
                            title="Filter"
                            aria-haspopup="listbox"
                            :aria-expanded="dropOpen"
                        >
                            <i class="fas fa-chevron-down" :class="{ 'rotate-180': dropOpen }" style="font-size:0.6rem; transition: transform 150ms"></i>
                        </button>

                        {{-- Dropdown panel --}}
                        <div
                            x-show="dropOpen"
                            @click.outside="dropOpen = false"
                            class="arch-module-tabs__overflow-popover arch-module-tabs__tab-dropdown-panel"
                            role="listbox"
                        >
                            @foreach ($tab['dropdown_items'] as $dropItem)
                                @if ($dropItem['separator'])
                                    <hr class="my-1 border-gray-200 dark:border-gray-600">
                                @else
                                    <button
                                        type="button"
                                        class="arch-module-tabs__overflow-item"
                                        @click.stop="
                                            activeLabel = '{{ $dropItem['label'] }}';
                                            @if (!empty($dropItem['switch_tab']))
                                                $store.moduleTabs.switchTo('{{ $dropItem['switch_tab'] }}', $wire);
                                                {{ !empty($dropItem['event']) ? "\$dispatch('{$dropItem['event']}', " . Illuminate\Support\Js::from($dropItem['payload']) . ");" : '' }}
                                            @elseif ($dropItem['event'] === 'architect:open-record')
                                                $wire.openTab({{ Illuminate\Support\Js::from($dropItem['payload']['type'] ?? '') }}, {{ Illuminate\Support\Js::from($dropItem['payload']['props'] ?? []) }}, {{ Illuminate\Support\Js::from($dropItem['payload']['fallback'] ?? '') }});
                                            @elseif (!empty($dropItem['event']))
                                                $store.moduleTabs.switchTo('{{ $tab['id'] }}', $wire);
                                                $dispatch('{{ $dropItem['event'] }}', {{ Illuminate\Support\Js::from($dropItem['payload']) }});
                                            @else
                                                $store.moduleTabs.switchTo('{{ $tab['id'] }}', $wire);
                                            @endif
                                            dropOpen = false
                                        "
                                        role="option"
                                    >
                                        <span x-show="activeLabel === '{{ $dropItem['label'] }}'" class="text-blue-500 text-xs w-4"><i class="fas fa-check"></i></span>
                                        <span x-show="activeLabel !== '{{ $dropItem['label'] }}'" class="w-4"></span>
                                        {{ $dropItem['label'] }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                @else
                    {{-- Standard tab button --}}
                    <button
                        type="button"
                        class="arch-module-tabs__tab group"
                        :class="{
                            'arch-module-tabs__tab--active':  $store.moduleTabs.activeId === '{{ $tab['id'] }}',
                            'arch-module-tabs__tab--dirty':   $store.moduleTabs.isDirty('{{ $tab['id'] }}'),
                            'arch-module-tabs__tab--saved':   $store.moduleTabs.isSaved('{{ $tab['id'] }}'),
                            'arch-module-tabs__tab--stale':   {{ $isStale ? 'true' : 'false' }},
                            'arch-module-tabs__tab--pinned':  {{ $tab['pinned'] ? 'true' : 'false' }},
                        }"
                        @click="$store.moduleTabs.switchTo('{{ $tab['id'] }}', $wire)"
                        @contextmenu.prevent="$store.moduleTabs.openContextMenu($event, '{{ $tab['id'] }}')"
                        :tabindex="$store.moduleTabs.activeId === '{{ $tab['id'] }}' ? 0 : -1"
                        title="{{ $tab['label'] }}"
                    >
                        {{-- Icon --}}
                        @if ($tab['icon'])
                            <i class="{{ $tab['icon'] }} arch-module-tabs__tab-icon" aria-hidden="true"></i>
                        @endif

                        {{-- Label --}}
                        <span class="arch-module-tabs__tab-label">{{ $tab['label'] }}</span>

                        {{-- Stale indicator --}}
                        @if ($isStale)
                            <span
                                role="button"
                                tabindex="0"
                                class="arch-module-tabs__stale-btn"
                                title="Data may have changed — click to refresh"
                                @click.stop="$wire.refreshTab('{{ $tab['id'] }}')"
                                @keydown.enter.stop="$wire.refreshTab('{{ $tab['id'] }}')"
                                @keydown.space.prevent.stop="$wire.refreshTab('{{ $tab['id'] }}')"
                            >
                                <i class="fas fa-rotate-right" aria-hidden="true"></i>
                            </span>
                        @endif

                        {{-- Close button (dynamic tabs only) --}}
                        @unless ($tab['pinned'])
                            <span
                                role="button"
                                tabindex="0"
                                class="arch-module-tabs__close"
                                title="Close tab"
                                @click.stop="$store.moduleTabs.requestClose('{{ $tab['id'] }}', $wire)"
                                @keydown.enter.stop="$store.moduleTabs.requestClose('{{ $tab['id'] }}', $wire)"
                                @keydown.space.prevent.stop="$store.moduleTabs.requestClose('{{ $tab['id'] }}', $wire)"
                                aria-label="Close {{ $tab['label'] }}"
                            >&times;</span>
                        @endunless
                    </button>
                @endif

            @endforeach
        </div>

        {{-- ── End-of-bar actions ─────────────────────────────────────── --}}
        <div class="arch-module-tabs__actions">

            {{-- Overflow popover: rendered by Alpine when tabs overflow --}}
            @if ($definition->showOverflowPopover)
                <div
                    class="arch-module-tabs__overflow"
                    x-show="$store.moduleTabs.overflowCount > 0"
                    x-data="{ open: false }"
                    x-cloak
                >
                    <button
                        type="button"
                        class="arch-module-tabs__overflow-btn"
                        @click="open = !open"
                        title="More open tabs"
                        x-text="`+${$store.moduleTabs.overflowCount} more`"
                    ></button>
                    <div
                        x-show="open"
                        x-cloak
                        x-transition
                        @click.outside="open = false"
                        class="arch-module-tabs__overflow-popover"
                    >
                        <template x-for="tab in $store.moduleTabs.overflowTabs" :key="tab.id">
                            <button
                                type="button"
                                class="arch-module-tabs__overflow-item"
                                @click="$store.moduleTabs.switchTo(tab.id, $wire); open = false"
                            >
                                <i class="fas" :class="tab.icon || 'fa-circle'" aria-hidden="true"></i>
                                <span x-text="tab.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            @endif

            {{-- Recently closed ↩ --}}
            @if ($definition->showRecentlyClosed && count($recentlyClosed) > 0)
                <div x-data="{ open: false }" class="relative">
                    <button
                        type="button"
                        class="arch-module-tabs__action-btn"
                        title="Reopen recently closed tab"
                        @click="open = !open"
                    >
                        <i class="fas fa-rotate-left" aria-hidden="true"></i>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        x-transition
                        @click.outside="open = false"
                        class="arch-module-tabs__overflow-popover"
                    >
                        @foreach ($recentlyClosed as $closed)
                            <button
                                type="button"
                                class="arch-module-tabs__overflow-item"
                                @click="$wire.openTab('{{ $closed['type'] }}', @js($closed['props'])); open = false"
                            >
                                @if ($closed['icon'])
                                    <i class="{{ $closed['icon'] }}" aria-hidden="true"></i>
                                @endif
                                <span>{{ $closed['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>{{-- /.arch-module-tabs__actions --}}
    </div>{{-- /.arch-module-tabs__bar --}}

    {{-- ── Content panels ─────────────────────────────────────────────── --}}
    <div class="arch-module-tabs__content">
        @foreach ($openTabs as $tab)
            <div
                x-show="$store.moduleTabs.activeId === '{{ $tab['id'] }}'"
                x-cloak
                tabindex="-1"
                class="arch-module-tabs__panel"
                @focus="$store.moduleTabs.activeId = '{{ $tab['id'] }}'"
            >
                @if ($tab['component'] !== '')
                    @if ($tab['mounted'])
                        @php
                            $version = $tab['version'] ?? 0;
                            $componentKey = $tab['id'] . ($version > 0 ? '-v' . $version : '');
                        @endphp
                        @livewire($tab['component'], $tab['props'], key($componentKey))
                    @else
                        {{-- Lazy placeholder — mounted on first activation via Alpine --}}
                        <div
                            class="arch-skeleton-wrapper p-6"
                            x-init="
                                if ($store.moduleTabs.activeId === '{{ $tab['id'] }}') {
                                    $wire.markMounted('{{ $tab['id'] }}');
                                }
                                $watch('$store.moduleTabs.activeId', id => {
                                    if (id === '{{ $tab['id'] }}') {
                                        $wire.markMounted('{{ $tab['id'] }}');
                                    }
                                });
                            "
                        >
                            <div class="arch-skeleton arch-skeleton--title mb-3"></div>
                            <div class="arch-skeleton arch-skeleton--text mb-2"></div>
                            <div class="arch-skeleton arch-skeleton--text arch-skeleton--short"></div>
                        </div>
                    @endif
                @endif
                {{-- No-content tabs (dropdown-only pinned tabs) render nothing here --}}

                {{-- Loading skeleton overlay while Livewire initialises --}}
                @if ($tab['component'] !== '')
                <div
                    wire:loading
                    wire:target="openTab,restoreTabs,markMounted"
                    class="arch-module-tabs__loading"
                >
                    <div class="arch-skeleton-wrapper p-6">
                        <div class="arch-skeleton arch-skeleton--title mb-3"></div>
                        <div class="arch-skeleton arch-skeleton--text mb-2"></div>
                        <div class="arch-skeleton arch-skeleton--text arch-skeleton--short"></div>
                    </div>
                </div>
                @endif
            </div>
        @endforeach
    </div>{{-- /.arch-module-tabs__content --}}

    {{-- ── Context menu (right-click) ────────────────────────────────── --}}
    <div
        x-show="$store.moduleTabs.contextMenu.open"
        x-cloak
        x-transition
        @click.outside="$store.moduleTabs.contextMenu.open = false"
        class="arch-module-tabs__context-menu"
        :style="`top: ${$store.moduleTabs.contextMenu.y}px; left: ${$store.moduleTabs.contextMenu.x}px`"
    >
        <button type="button" class="arch-module-tabs__context-item"
            @click="$store.moduleTabs.requestClose($store.moduleTabs.contextMenu.tabId, $wire); $store.moduleTabs.contextMenu.open = false"
            x-show="!$store.moduleTabs.isPinned($store.moduleTabs.contextMenu.tabId)"
        >Close</button>
        <button type="button" class="arch-module-tabs__context-item"
            @click="$store.moduleTabs.closeOthers($store.moduleTabs.contextMenu.tabId, $wire); $store.moduleTabs.contextMenu.open = false"
        >Close Others</button>
        <button type="button" class="arch-module-tabs__context-item"
            @click="$store.moduleTabs.closeAllToRight($store.moduleTabs.contextMenu.tabId, $wire); $store.moduleTabs.contextMenu.open = false"
        >Close All to Right</button>
        <div class="arch-module-tabs__context-divider"></div>
        <button type="button" class="arch-module-tabs__context-item arch-module-tabs__context-item--danger"
            @click="$store.moduleTabs.closeAll($wire); $store.moduleTabs.contextMenu.open = false"
        >Close All</button>
    </div>

</div>{{-- /.arch-module-tabs --}}
@endif
