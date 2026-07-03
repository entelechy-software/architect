{{--
    ModuleTable engine — index/list view.

    Tabler 1.0.0-beta17 card containing:
      - search input + active-filter chips + clear-filters / archived toggle
      - per-filter controls (rendered from each ModuleTableFilter's blade())
      - a sortable, paginated table

    Permissions enforcement (Layer 4 / column visibility) is performed in
    the Livewire component before the view is rendered: $columns and $rows
    arriving here have already had hidden keys stripped.
--}}
@php
    /*
     * Table-level inline-edit metadata computed once per render:
     *   $rowDependentEditKeys  — editKeys that are inter-dependent (cascade/cross-field rules) → row-mode
     *   $cascadeChildren       — parent editKey → list of child editKeys to clear on change
     *   $cascadeChildrenJson   — JSON-encoded for Alpine initialisation in x-data
     *   $inlineUnsupportedTypes — input types that cannot be rendered inline
     */
    $rowDependentEditKeys = $definition->modifyMode === 'inline'
        ? $definition->getRowDependentEditKeys()
        : [];

    $cascadeChildren = [];
    foreach ($definition->columns as $_col) {
        $_parent = $_col->getCascadeFrom();
        if ($_parent !== null) {
            $cascadeChildren[$_parent][] = $_col->getEditKey();
        }
    }

    $inlineUnsupportedTypes = ['hidden', 'wysiwyg', 'upload', 'multiselect', 'color', 'icon', 'duallistbox'];
    $cascadeChildrenJson = json_encode($cascadeChildren, JSON_HEX_APOS | JSON_HEX_QUOT);
    $customFormReturnQueryKeys = array_values(array_filter(array_unique([
        $definition->customCreateForm?->callbackQueryKey,
        $definition->customModifyForm?->callbackQueryKey,
    ])));
    $customFormPostMessageEnabled = (bool) (
        ($definition->customCreateForm?->postMessageRefresh ?? false)
        || ($definition->customModifyForm?->postMessageRefresh ?? false)
    );
@endphp
@if ($definition->headerSection)
    @include('architect::stats.render-section', [
        'section'     => $definition->headerSection,
        'data'        => [],
        'granularity' => 'D',
    ])
@endif

<div
    class="relative"
    data-loading="{{ $isLoading ? 'true' : 'false' }}"
    @if (config('architect.animations', true) && $definition->animateButtons) data-arch-anim-buttons @endif
    x-data="moduleTable({
        instanceKey:             '{{ $instanceKey }}',
        tablePrefix:             'moduleTable_{{ md5($definitionClass) }}_u{{ (int) (auth()->id() ?? 0) }}_',
        persistenceEnabled:      {{ $definition->filterPersistence ? 'true' : 'false' }},
        bookmarkFiltersEnabled:  {{ $definition->filterBookmarkFilters ? 'true' : 'false' }},
        cascadeChildren:         {{ $cascadeChildrenJson }},
        definitionMd5:           '{{ md5($definitionClass) }}',
        autoRefreshSeconds:      {{ (int) $definition->autoRefreshSeconds }},
        autoRefreshFingerprintOn: @js($definition->autoRefreshFingerprintOn),
        customFormReturnQueryKeys: @js($customFormReturnQueryKeys),
        customFormPostMessageEnabled: {{ $customFormPostMessageEnabled ? 'true' : 'false' }},
        supersearchHookId:       @php
            use Entelechy\Architect\Supersearch\Contracts\HasSupersearchHook;
            echo is_a($definitionClass, HasSupersearchHook::class, true) ? "'" . $this->getId() . "'" : 'null';
        @endphp,
    })"
    @edit-saved.window="showEditSuccess = true; setTimeout(() => showEditSuccess = false, 2000); rowEdit.cancel()"
    @inline-edit:error.window="editErrorMessage = $event.detail.message; showEditError = true; setTimeout(() => showEditError = false, 4000)"
    @row-edit:errors.window="rowEdit.applyErrors($event.detail.rowId, $event.detail.errors)"
    {{--
        Mirror this engine's reactive state into the page-wide Alpine store so
        external Alpine/Livewire components can subscribe without tight coupling.
        Re-evaluated on every Livewire payload because $wire.* are reactive proxies.
    --}}
    x-effect="
        window.Alpine.store('moduleTable')['{{ $instanceKey }}'] = {
            _registered:     true,
            filters:         $wire.filters,
            search:          $wire.search,
            total:           {{ (int) $total }},
            selected:        $wire.selected,
            sort:            $wire.sortColumn,
            sortDir:         $wire.sortDirection,
            includeArchived: $wire.includeArchived,
            scope:           $wire.scope,
        };
    "
>
    {{-- Duplicate-instance error banner. Shown by Alpine when the same
         definition class is mounted more than once on the page. --}}
    <div x-show="_duplicateError" x-cloak class="arch-alert arch-alert-danger m-3">
        <strong>ModuleTable configuration error:</strong>
        The definition <code>{{ class_basename($definitionClass) }}</code> has already been mounted on this page.
    </div>

    @if ($hasError)
        <x-architect::callout type="danger" class="m-3">{{ $errorMessage }}</x-architect::callout>
    @endif
    {{-- Custom alerts (->alert()) plus auto-detected defaults (read-only banner). --}}
    @php
        $_allAlerts = $definition->alerts;
        if (! $definition->suppressAutoAlerts
            && ! $definition->creatable
            && ! $definition->modifiable) {
            $_allAlerts[] = ['type' => 'info', 'message' => 'This table is view-only.'];
        }
    @endphp
    @foreach ($_allAlerts as $_alert)
        <div class="arch-alert alert-{{ $_alert['type'] }} m-3" role="alert">{{ $_alert['message'] }}</div>
    @endforeach
    {{-- Success toast for inline editing --}}
    <div
        x-show="showEditSuccess"
        x-transition
        class="fixed top-0 right-0 p-3 mt-toast-overlay"
    >
        <div class="arch-alert arch-alert-success flex items-center mb-0" role="alert">
            <i class="fas fa-check-circle ml-2"></i>
            <div>Changes saved successfully</div>
        </div>
    </div>
    {{-- Error toast for inline editing --}}
    <div
        x-show="showEditError"
        x-transition
        class="fixed top-0 right-0 p-3 mt-toast-overlay"
    >
        <div class="arch-alert arch-alert-danger flex items-center mb-0" role="alert">
            <i class="fas fa-exclamation-circle ml-2"></i>
            <div x-text="editErrorMessage"></div>
        </div>
    </div>

<x-architect::table.shell>
{{-- Navigator (position = top, rendered above the card) --}}
@if ($definition->navigator && $definition->navigator->position !== 'bottom')
    <x-architect::definition-renderer :definition="$definition->navigator" />
@endif

<div class="arch-card">
    {{-- Card header: title only --}}
    @if ($definition->title)
        <div class="arch-card-header bg-gray-100 dark:bg-gray-700/50">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-0">{{ $definition->title }}</h3>
        </div>
    @endif

    <div class="arch-card-body border-b border-gray-200 dark:border-white/10 module-table-toolbar">
        <div class="flex flex-wrap gap-2 items-center justify-between">

            {{-- Left side: New button (if applicable) + Search --}}
            <div class="flex flex-wrap gap-2 items-center">

                {{-- Create button (Layer 2 gate is enforced by FormPanel) --}}
                @if ($definition->creatable)
                    @if ($definition->customCreateForm)
                        @php
                            $customCreate = $definition->customCreateForm;
                            $createUrl = $customCreate->url ?? '';
                        @endphp

                        @if ($customCreate->mode === 'tabs-manager')
                            <x-architect::button
                                size="sm"
                                icon="heroicon-m-plus"
                                @click="$dispatch('architect:open-record', { type: '{{ $customCreate->tabType }}', props: {}, fallback: '{{ $createUrl }}' })"
                            >
                                New
                            </x-architect::button>
                        @elseif ($customCreate->mode === 'same-window-page')
                            <x-architect::button
                                size="sm"
                                icon="heroicon-m-plus"
                                @click="window.location.href='{{ $createUrl }}'"
                            >
                                New
                            </x-architect::button>
                        @elseif ($customCreate->mode === 'new-window')
                            <x-architect::button
                                size="sm"
                                icon="heroicon-m-plus"
                                @click="(() => {
                                    const _u = new URL('{{ $createUrl }}', window.location.origin);
                                    const _return = new URL(window.location.href);
                                    _return.searchParams.set('{{ $customCreate->callbackQueryKey ?? 'architect_refresh' }}', '{{ $instanceKey }}');
                                    _u.searchParams.set('architect_table_instance', '{{ $instanceKey }}');
                                    _u.searchParams.set('architect_table_refresh_key', '{{ $customCreate->callbackQueryKey ?? 'architect_refresh' }}');
                                    _u.searchParams.set('architect_table_return_url', _return.toString());
                                    window.open(_u.toString(), '_blank', 'noopener');
                                })()"
                            >
                                New
                            </x-architect::button>
                        @else
                            <x-architect::button
                                size="sm"
                                icon="heroicon-m-plus"
                                @click="$dispatch('architect:open-custom-form', {
                                    definitionClass: '{{ addslashes($definitionClass) }}',
                                    title: 'New {{ addslashes($definition->title ?? 'Record') }}',
                                    customDefinitionClass: '{{ addslashes($customCreate->definitionClass) }}',
                                    customMode: '{{ $customCreate->mode }}'
                                })"
                            >
                                New
                            </x-architect::button>
                        @endif
                    @elseif ($definition->createOpenInTab && $definition->createTabType)
                        <x-architect::button
                            size="sm"
                            icon="heroicon-m-plus"
                            @click="$dispatch('architect:open-record', { type: '{{ $definition->createTabType }}', props: {}, fallback: '{{ $definition->createUrl ?? '' }}' })"
                        >
                            New
                        </x-architect::button>
                    @else
                        <x-architect::button
                            size="sm"
                            icon="heroicon-m-plus"
                            @click="$dispatch('architect:open-create', { definitionClass: '{{ addslashes($definitionClass) }}' })"
                        >
                            New
                        </x-architect::button>
                    @endif
                @endif

                {{-- Search --}}
                @if (!$definition->hideSearchBar)
                <div class="mt-search-group">
                    <i class="fas fa-search mt-search-icon"></i>
                    <input
                        type="search"
                        class="arch-input"
                        placeholder="Search…"
                        wire:model.live.debounce.300ms="search"
                        aria-label="Search"
                    >
                </div>
                @endif

            </div>

            {{-- Controls — right side, order: filters, refresh, print, columns, import, export, archived --}}
            <div class="arch-table-controls flex flex-wrap gap-2 items-center">

                {{-- 1. Filters button — shown when there are declared filters OR when the table is archivable. --}}
                @if (count($definition->filters) > 0 || $definition->archivable)
                    <div class="flex gap-0.5" role="group" aria-label="Filter controls">
                        <x-architect::button
                            size="sm"
                            :outlined="empty($filters) && ! $includeArchived"
                            color="warning"
                            icon="heroicon-m-funnel"
                            @click="openFilters()"
                        >
                            @if (!empty($filters))
                                <span
                                    class="arch-badge arch-badge-secondary text-xs"
                                    x-text="(n => n.length > 18 ? n.slice(0,17) + '\u2026' : n)(_activeBookmarkedFilterLabel()) || '{{ count($filters) }}'"
                                >{{ count($filters) }}</span>
                            @endif
                        </x-architect::button>
                        @if (!empty($filters))
                            <x-architect::button
                                color="warning"
                                outlined
                                icon="heroicon-m-x-mark"
                                label-sr-only
                                wire:click="clearFilters"
                                aria-label="Clear all filters"
                            />
                        @endif
                    </div>
                @endif

                {{-- 2. Refresh button — reloads data preserving all current state (search/filters/sort/page).
                     When auto-refresh is enabled the button shows a persistent countdown ring; clicking
                     it resets the countdown and refreshes immediately. In manual-only mode a drain arc
                     appears for the 2-second post-click lock-out.
                     The icon and label are always visible regardless of mode. --}}
                <x-architect::button
                    size="sm"
                    color="gray"
                    outlined
                    @click="arRefresh()"
                    x-bind:disabled="_arLoading"
                    aria-label="Refresh table data"
                    x-bind:title="_arInterval > 0 ? 'Refresh (auto in ' + _arRemaining + 's)' : 'Refresh table data'"
                >
                    <span x-show="_arInterval > 0 || _arLoading" style="display:none" class="fi-btn-icon-ctn text-gray-500 dark:text-gray-400">
                        <svg width="14" height="14" viewBox="0 0 20 20" class="mt-refresh-spinner ml-1">
                            <circle
                                cx="10" cy="10" r="8"
                                fill="none" stroke="currentColor" stroke-width="2"
                                stroke-dasharray="50.27"
                                :stroke-dashoffset="50.27 * (1 - ((_arInterval > 0 ? (_arRemaining / _arInterval) : (_arManualRemaining / _arManualDuration)) || 0))"
                                stroke-linecap="round"
                            ></circle>
                        </svg>
                    </span>
                    <span x-show="_arInterval === 0 && !_arLoading" style="display:none" class="fi-btn-icon-ctn text-gray-500 dark:text-gray-400">
                        <i class="fas fa-rotate-right h-4 w-4"></i>
                    </span>
                </x-architect::button>

                {{-- 4b. Built-in CSV Import button (when ->importable() declared) --}}
                @if ($definition->importDefinition && app(\Entelechy\Architect\Contracts\PermissionResolver::class)->can(auth()->user(), $definition->importDefinition->permission))
                    <x-architect::button
                        size="sm"
                        color="gray"
                        outlined
                        icon="heroicon-m-arrow-up-tray"
                        @click="$dispatch('architect:open-import', { definitionClass: '{{ addslashes($definitionClass) }}' })"
                    />
                @endif

                {{-- 6. Export dropdown (Alpine-driven) --}}
                @if (count($definition->exportFormats) > 0)
                    <div class="arch-dropdown" x-data="{ exportOpen: false }" @click.outside="exportOpen = false">
                        <x-architect::button
                            size="sm"
                            color="gray"
                            outlined
                            icon="heroicon-m-arrow-down-tray"
                              x-bind:aria-expanded="exportOpen"
                            @click="exportOpen = !exportOpen"
                        />
                        <ul
                            class="arch-dropdown-menu"
                            x-show="exportOpen"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            @click="exportOpen = false"
                        >
                            @foreach ($definition->exportFormats as $format)
                                @php
                                    $_exportIcon = match ($format) {
                                        'csv'   => 'fa-file-csv',
                                        'excel' => 'fa-file-excel',
                                        'pdf'   => 'fa-file-pdf',
                                        'html'  => 'fa-file-code',
                                        'print' => 'fa-print',
                                        default => 'fa-file',
                                    };
                                    $_exportLabel = match ($format) {
                                        'print' => 'Print',
                                        default => strtoupper($format),
                                    };
                                @endphp
                                <li>
                                    <button class="arch-dropdown-item" wire:click="export('{{ $format }}')">
                                        <i class="fas {{ $_exportIcon }}"></i>
                                        {{ $_exportLabel }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 7. Column visibility dropdown (Alpine-driven) --}}
                @if (count($columns) > 0)
                    <div class="arch-dropdown" x-data="{ colsOpen: false }" @click.outside="colsOpen = false">
                        <x-architect::button
                            size="sm"
                            color="gray"
                            outlined
                            icon="heroicon-m-view-columns"
                              x-bind:aria-expanded="colsOpen"
                            @click="colsOpen = !colsOpen"
                        />
                        <ul
                            class="arch-dropdown-menu"
                            x-show="colsOpen"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                        >
                            @foreach ($columns as $column)
                                <li>
                                    <button
                                        class="arch-dropdown-item"
                                        @click.stop="toggleCol('{{ $column->getKey() }}')"
                                    >
                                        <i class="far fa-check-square mt-col-icon" x-show="!isColHidden('{{ $column->getKey() }}')"></i>
                                        <i class="far fa-square mt-col-icon" x-show="isColHidden('{{ $column->getKey() }}')"></i>
                                        {{ $column->getLabel() }}
                                    </button>
                                </li>
                            @endforeach
                            <li class="arch-dropdown-divider"></li>
                            <li>
                                <button class="arch-dropdown-item" @click.stop="resetCols(); colsOpen = false">
                                    <i class="fas fa-undo text-gray-400"></i>
                                    Reset columns
                                </button>
                            </li>
                        </ul>
                    </div>
                @endif

                {{-- 5. Custom header actions (e.g. Import) — icon only, label shown as tooltip --}}
                @foreach ($definition->headerActions as $headerAction)
                    @php
                        $canUseAction = $headerAction->getPermission() === null || app(\Entelechy\Architect\Contracts\PermissionResolver::class)->can(auth()->user(), $headerAction->getPermission());
                          $headerActionIcon = $headerAction->getIcon();
                          $headerActionUsesLegacyIcon = is_string($headerActionIcon)
                              && (str_contains($headerActionIcon, ' ') || str_starts_with($headerActionIcon, 'fa'));
                          $headerActionUsesBladeIcon = is_string($headerActionIcon)
                              && ! $headerActionUsesLegacyIcon;
                          $headerActionWireClick = $headerAction->getWireClick();
                          $headerActionAnimation = $headerAction->getAnimation();
                          $headerActionAnimClass = ($headerActionAnimation && $headerActionAnimation !== 'loading')
                              ? 'arch-btn--anim-' . $headerActionAnimation
                              : '';
                    @endphp
                    @if ($canUseAction)
                          @if ($headerAction->getOpenInTab())
                              {{-- ModuleTabs intent event: dispatches architect:open-record so the tab bar
                                   opens a new tab. Falls back to URL navigation when no
                                   ModuleTabsManager is present on the page. --}}
                              @php
                                  $hOpenInTab  = $headerAction->getOpenInTab();
                                  $hTabType    = $hOpenInTab['type'];
                                  $hTabFallback = $hOpenInTab['fallback'];
                              @endphp
                              <x-architect::button
                                  size="sm"
                                  :color="$headerAction->getColor()"
                                  :outlined="! $headerAction->isFilled()"
                                  :icon="$headerActionUsesBladeIcon ? $headerActionIcon : null"
                                  :tooltip="$headerAction->getLabel()"
                                  class="{{ $headerActionAnimClass }}"
                                  @click="$dispatch('architect:open-record', { type: '{{ $hTabType }}', props: {}, fallback: '{{ $hTabFallback }}' })"
                              >
                                  @if ($headerActionUsesLegacyIcon)
                                      <i class="{{ $headerActionIcon }}"></i>
                                  @elseif (! $headerActionUsesBladeIcon)
                                      {{ $headerAction->getLabel() }}
                                  @endif
                              </x-architect::button>
                          @elseif ($headerActionWireClick)
                              @if ($headerActionAnimation === 'loading')
                                  <x-architect::button
                                      size="sm"
                                      :color="$headerAction->getColor()"
                                      :outlined="! $headerAction->isFilled()"
                                      :icon="$headerActionUsesBladeIcon ? $headerActionIcon : null"
                                      :href="$headerAction->getUrl()"
                                      :target="$headerAction->isNewWindow() ? '_blank' : null"
                                      :tooltip="$headerAction->getLabel()"
                                      wire:click="{{ $headerActionWireClick }}"
                                      wire:loading.class="arch-btn--loading"
                                      wire:loading.attr="disabled"
                                      wire:target="{{ $headerActionWireClick }}"
                                  >
                                      @if ($headerActionUsesLegacyIcon)
                                          <i class="{{ $headerActionIcon }}"></i>
                                      @elseif (! $headerActionUsesBladeIcon)
                                          {{ $headerAction->getLabel() }}
                                      @endif
                                  </x-architect::button>
                              @else
                                  <x-architect::button
                                      size="sm"
                                      :color="$headerAction->getColor()"
                                      :outlined="! $headerAction->isFilled()"
                                      :icon="$headerActionUsesBladeIcon ? $headerActionIcon : null"
                                      :href="$headerAction->getUrl()"
                                      :target="$headerAction->isNewWindow() ? '_blank' : null"
                                      :tooltip="$headerAction->getLabel()"
                                      wire:click="{{ $headerActionWireClick }}"
                                      class="{{ $headerActionAnimClass }}"
                                  >
                                      @if ($headerActionUsesLegacyIcon)
                                          <i class="{{ $headerActionIcon }}"></i>
                                      @elseif (! $headerActionUsesBladeIcon)
                                          {{ $headerAction->getLabel() }}
                                      @endif
                                  </x-architect::button>
                              @endif
                          @else
                              <x-architect::button
                                  size="sm"
                                  :color="$headerAction->getColor()"
                                  :outlined="! $headerAction->isFilled()"
                                  :icon="$headerActionUsesBladeIcon ? $headerActionIcon : null"
                                  :href="$headerAction->getUrl()"
                                  :target="$headerAction->isNewWindow() ? '_blank' : null"
                                  :tooltip="$headerAction->getLabel()"
                                  class="{{ $headerActionAnimClass }}"
                              >
                                  @if ($headerActionUsesLegacyIcon)
                                      <i class="{{ $headerActionIcon }}"></i>
                                  @elseif (! $headerActionUsesBladeIcon)
                                      {{ $headerAction->getLabel() }}
                                  @endif
                              </x-architect::button>
                          @endif
                    @endif
                @endforeach



            </div>
        </div>
    </div>{{-- end card-body --}}

    @php
        $tableWrapClass = 'arch-table-wrap mt-table-scroll';
        $tableWrapStyle = '';
        if ($definition->scrollMode === 'container') {
            $tableWrapClass .= ' arch-table-wrap--container';
            if ($definition->visibleRows !== null) {
                $tableWrapStyle = 'max-height:calc(' . $definition->visibleRows . '*3.25rem + 2.75rem)';
            }
        }
    @endphp
    <div class="{{ $tableWrapClass }}"@if($tableWrapStyle) style="{{ $tableWrapStyle }}"@endif>
        <table class="arch-table arch-table-hover card-table">
            <thead>
                <tr>
                    @if ($definition->selectableRows)
                        @php
                            $pageIds = array_values(array_map(fn ($r) => (int) ($r['id'] ?? 0), $rows));
                            $allOnPageSelected = $pageIds !== [] && array_diff($pageIds, $selected) === [];
                        @endphp
                        <th class="w-1">
                            <input
                                type="checkbox"
                                class="arch-check-input"
                                wire:click="setSelection({{ json_encode($pageIds) }}, {{ $allOnPageSelected ? 'false' : 'true' }})"
                                :checked="{{ json_encode($pageIds) }}.length > 0 && {{ json_encode($pageIds) }}.every(id => ($wire.selected ?? []).includes(id))"
                                aria-label="Select all visible rows"
                            >
                        </th>
                    @endif
                    @foreach ($columns as $column)
                        <th
                            @class(['cursor-pointer' => $column->isSortable()])
                            x-show="!isColHidden('{{ $column->getKey() }}')"
                        >
                            @if ($column->isSortable())
                                <button
                                    type="button"
                                    class="arch-btn arch-btn-link p-0 no-underline font-bold"
                                    wire:click="toggleSort('{{ $column->getKey() }}')"
                                >
                                    {{ $column->getLabel() }}
                                    @if ($sortColumn === $column->getKey())
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} mr-1 text-sm"></i>
                                    @else
                                        <i class="fas fa-sort mr-1 text-sm text-gray-500 dark:text-gray-400"></i>
                                    @endif
                                </button>
                            @else
                                {{ $column->getLabel() }}
                            @endif
                        </th>
                    @endforeach
                    <th class="w-1 text-right"></th>
                </tr>
            </thead>
            <tbody
                class="arch-table-body"
                @if (config('architect.animations', true) && $definition->animateRows) data-arch-anim-rows @endif
            >
                {{-- Table-level inline-edit vars ($rowDependentEditKeys, $cascadeChildren,
                     $cascadeChildrenJson, $inlineUnsupportedTypes) are computed at the very
                     top of this template (before <div x-data>) so they can be seeded into
                     Alpine's x-data initialization without placing invalid HTML inside <tbody>. --}}

                @forelse ($rows as $row)
                    @php
                        $rowId = (int) ($row['id'] ?? 0);

                        // Pre-stringify the row's editable values for row-mode start.
                        // We only seed keys that match an editable column's editKey.
                        $rowEditValues = [];
                        foreach ($definition->columns as $_col) {
                            if (! $_col->isEditable() || $_col->getModifyInline() === false) continue;
                            $_ek  = $_col->getEditKey();
                            $_val = $row[$_ek] ?? ($row[$_col->getKey()] ?? '');
                            // Convert d/m/Y display dates to ISO Y-m-d so <input type="date">
                            // renders correctly and Laravel's 'date' validation rule accepts it.
                            if ($_col->getType() === 'date' && is_string($_val) && strlen($_val) === 10) {
                                $_dt = \DateTime::createFromFormat('d/m/Y', $_val);
                                if ($_dt) {
                                    $_val = $_dt->format('Y-m-d');
                                }
                            }
                            $rowEditValues[$_ek] = $_val;
                        }
                        $rowEditValuesJson = json_encode($rowEditValues, JSON_HEX_APOS | JSON_HEX_QUOT);
                    @endphp
                    <tr
                        wire:key="row-{{ $row['id'] ?? $loop->index }}"
                        @if (config('architect.animations', true) && $definition->animateRows)
                        x-data="{ _flash: false }"
                        @architect:row-saved.window="if ($event.detail.id == {{ $rowId }}) { _flash = true; setTimeout(() => _flash = false, 1400); }"
                        :class="{
                            'arch-row--editing':   rowEdit.isActive({{ $rowId }}),
                            'arch-row--deleting':  $wire.pendingDeleteId   === {{ $rowId }},
                            'arch-row--archiving': $wire.pendingArchiveId  === {{ $rowId }},
                            'arch-row--saved':     _flash,
                        }"
                        @else
                        :class="rowEdit.isActive({{ $rowId }}) ? 'arch-row--editing' : ''"
                        @endif
                    >
                        @if ($definition->selectableRows)
                            <td>
                                <input
                                    type="checkbox"
                                    class="arch-check-input"
                                    wire:click="toggleSelect({{ (int) ($row['id'] ?? 0) }})"
                                    :checked="($wire.selected ?? []).includes({{ (int) ($row['id'] ?? 0) }})"
                                    aria-label="Select row"
                                >
                            </td>
                        @endif
                        @foreach ($columns as $column)
                            @php
                                $value = $row[$column->getKey()] ?? '';
                                $rowId = (int) ($row['id'] ?? 0);
                                $colType = $column->getType();
                                $editKey = $column->getEditKey();
                                $columnVisibleNode = $column->visibilityNodeForMode(false);
                                $columnEditableNode = $column->editabilityNodeForMode(false);
                                $canInlineByPermission = true;
                                if ($columnVisibleNode !== null) {
                                    $canInlineByPermission = app(\Entelechy\Architect\Table\Permissions\PermissionGate::class)->userCan(auth()->user(), $columnVisibleNode);
                                }
                                if ($canInlineByPermission && $columnEditableNode !== null) {
                                    $canInlineByPermission = app(\Entelechy\Architect\Table\Permissions\PermissionGate::class)->userCan(auth()->user(), $columnEditableNode);
                                }

                                // A column is "inline-eligible" iff:
                                //   - table is in modifyMode 'inline'
                                //   - column has an editable type
                                //   - column is NOT toggleable (those carry their own switch UI)
                                //   - column type IS supported inline
                                //   - column wasn't explicitly opted out via ->modifyInline(false)
                                $inlineEligible = $definition->modifyMode === 'inline'
                                    && $column->isEditable()
                                    && ! $column->isToggleable()
                                    && ! in_array($colType, $inlineUnsupportedTypes, true)
                                    && $column->getModifyInline() !== false
                                    && $canInlineByPermission
                                    && in_array($column->getKey(), array_map(fn($c) => $c->getKey(), $definition->getModifyColumns()), true);

                                // Click resolution: row-mode vs cell-mode.
                                $hasDependency = isset($rowDependentEditKeys[$editKey]);
                                $forceCellMode = $column->getModifyInline() === true;
                                $opensRowOnClick = $inlineEligible && $hasDependency && ! $forceCellMode;
                                $opensCellOnClick = $inlineEligible && ! $opensRowOnClick;

                                // For lookup columns, pre-compute the remote options URL at render time.
                                $lookupUrl = ($inlineEligible && $colType === 'lookup')
                                    ? (\Entelechy\Architect\Table\Http\LookupController::urlFor($definitionClass, $column->getKey()))
                                    : '';

                                // PHP-encoded options for static select (JSON-safe).
                                $staticOptions = ($inlineEligible && $colType === 'select')
                                    ? json_encode($column->getOptions() ?? [], JSON_HEX_APOS | JSON_HEX_QUOT)
                                    : '{}';
                            @endphp
                            <td
                                x-show="!isColHidden('{{ $column->getKey() }}')"
                                @if ($inlineEligible)
                                    @class(['mt-inline-editable'])
                                    title="Click to edit"
                                @endif
                                @if ($opensRowOnClick)
                                    @click.self='rowEdit.isActive({{ $rowId }}) || rowEdit.start({{ $rowId }}, {!! $rowEditValuesJson !!})'
                                @elseif ($opensCellOnClick)
                                    @click.self="rowEdit.isActive({{ $rowId }}) || inlineEdit.start({{ $rowId }}, '{{ $column->getKey() }}', @js((string) $value), $wire)"
                                @endif
                            >
                                @if ($inlineEligible)
                                    {{-- Display span: hidden whenever this cell is being edited
                                         (cell-mode for this cell, OR row-mode for this row). --}}
                                    <span
                                        x-show="!(inlineEdit.rowId === {{ $rowId }} && inlineEdit.columnKey === '{{ $column->getKey() }}') && !rowEdit.isActive({{ $rowId }})"
                                        @if ($opensRowOnClick)
                                            @click='rowEdit.start({{ $rowId }}, {!! $rowEditValuesJson !!})'
                                        @else
                                            @click="inlineEdit.start({{ $rowId }}, '{{ $column->getKey() }}', @js((string) $value), $wire)"
                                        @endif
                                        class="mt-cell-display"
                                    >
                                @endif

                                    @if ($column->isToggleable())
                                        @php
                                            $isChecked = (bool) $value;
                                            $isArchivedRow = (bool) ($row['archived'] ?? false);
                                            $togglePermission = $column->getTogglePermission() ?? $definition->permissions->modify;
                                            $canToggle = app(\Entelechy\Architect\Contracts\PermissionResolver::class)->can(auth()->user(), $togglePermission);
                                            $toggleLabel = $isChecked ? $column->getToggleOnLabel() : $column->getToggleOffLabel();
                                        @endphp
                                        <div class="arch-check arch-switch mb-0 flex items-center gap-2">
                                            <input
                                                class="arch-switch-input"
                                                type="checkbox"
                                                role="switch"
                                                id="toggle-{{ $column->getKey() }}-{{ $row['id'] ?? 0 }}"
                                                @checked($isChecked)
                                                @disabled(! $canToggle || $isArchivedRow)
                                                @if ($canToggle && ! $isArchivedRow)
                                                    wire:click="toggleColumn('{{ $column->getKey() }}', {{ (int) ($row['id'] ?? 0) }})"
                                                    wire:loading.attr="disabled"
                                                @endif
                                            >
                                            <label
                                                class="arch-check-label text-gray-500 dark:text-gray-400 text-sm mb-0"
                                                for="toggle-{{ $column->getKey() }}-{{ $row['id'] ?? 0 }}"
                                            >{{ $toggleLabel }}</label>
                                        </div>
                                    @elseif ($column->isBadge())
                                        @php
                                            $badge = $column->getBadgeProfileForValue($value);
                                            $badgeText = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
                                        @endphp
                                        <span class="arch-badge inline-flex items-center gap-1" data-variant="soft" data-color="{{ $badge['color'] }}" data-size="sm">
                                            @if ($badge['icon'] && $badge['position'] === 'left')
                                                <i class="{{ $badge['icon'] }}" aria-hidden="true"></i>
                                            @endif
                                            {{ $badgeText }}
                                            @if ($badge['icon'] && $badge['position'] === 'right')
                                                <i class="{{ $badge['icon'] }}" aria-hidden="true"></i>
                                            @endif
                                        </span>
                                    @else
                                        {{ $value }}
                                    @endif
                                @if ($inlineEligible)
                                    </span>
                                    {{-- Edit input: created/destroyed by x-if so lookup controls can init/destroy cleanly. --}}
                                    <template x-if="inlineEdit.rowId === {{ $rowId }} && inlineEdit.columnKey === '{{ $column->getKey() }}'">
                                        @if ($colType === 'checkbox')
                                        <div
                                            x-init="
                                                $nextTick(() => {
                                                    const el = $el.querySelector('input');
                                                    if (el) el.focus();
                                                });
                                            "
                                            @keydown.escape.window="inlineEdit.cancel()"
                                        >
                                            <input
                                                type="checkbox"
                                                class="arch-check-input"
                                                :checked="inlineEdit.value === '1' || inlineEdit.value === true || inlineEdit.value === 1"
                                                @change="inlineEdit.value = $event.target.checked ? '1' : '0'; inlineEdit.commit($wire)"
                                            >
                                        </div>
                                        @elseif ($colType === 'select')
                                        <div
                                            x-init="$nextTick(() => { const el = $el.querySelector('select'); if (el) el.focus(); })"
                                            @keydown.escape.window="inlineEdit.cancel()"
                                        >
                                            <select
                                                class="mt-inline-edit"
                                                @change="inlineEdit.value = $event.target.value; inlineEdit.commit($wire)"
                                                x-init="$el.value = inlineEdit.value"
                                            >
                                                @foreach ($column->getOptions() ?? [] as $optValue => $optLabel)
                                                    <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @elseif ($colType === 'lookup')
                                        {{-- Inline-edit AJAX lookup combobox. --}}
                                        <div
                                            x-data="architectCombobox({
                                                url:      '{{ $lookupUrl }}',
                                                value:    String(inlineEdit.value ?? ''),
                                                onChange: function(v) {
                                                    inlineEdit.value = v ?? '';
                                                    if (v) $nextTick(() => inlineEdit.commit($wire));
                                                },
                                            })"
                                            @click.outside="inlineEdit.cancel()"
                                            @keydown.escape.window="inlineEdit.cancel()"
                                            x-init="openDropdown()"
                                            class="relative"
                                        >
                                            <button
                                                type="button"
                                                class="arch-combobox-trigger open"
                                                @click="toggleDropdown()"
                                                :aria-expanded="open"
                                            >
                                                <span x-show="hasValue" x-text="selectedLabel" class="arch-combobox-value"></span>
                                                <span x-show="!hasValue" class="arch-combobox-placeholder">{{ __('Select…') }}</span>
                                                <span class="arch-combobox-chevron"><i class="fas fa-chevron-down"></i></span>
                                            </button>
                                            <div class="arch-combobox-dropdown" x-show="open" x-transition role="listbox">
                                                <div class="arch-combobox-search-wrap">
                                                    <input x-ref="search" type="text" class="arch-combobox-search"
                                                        x-model="query" @input="onQueryInput()"
                                                        placeholder="{{ __('Search…') }}" autocomplete="off">
                                                </div>
                                                <ul x-ref="optionList" class="arch-combobox-options">
                                                    <div class="arch-combobox-loading" x-show="loading">
                                                        <i class="fas fa-circle-notch fa-spin text-gray-400"></i>
                                                    </div>
                                                    <template x-for="(opt, idx) in options" :key="opt.id">
                                                        <li class="arch-combobox-option"
                                                            :class="{ active: activeIdx === idx, selected: isSelected(opt.id) }"
                                                            @click="select(opt.id, opt.text)" role="option">
                                                            <span class="arch-combobox-check"><i class="fas fa-check" x-show="isSelected(opt.id)"></i></span>
                                                            <span x-text="opt.text"></span>
                                                        </li>
                                                    </template>
                                                    <li class="arch-combobox-empty" x-show="!loading && !options.length && query">{{ __('No results.') }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                        @elseif ($colType === 'multiselect')
                                        @php
                                            $_msData = collect($column->getOptions() ?? [])
                                                ->map(fn($label, $val) => ['id' => (string) $val, 'text' => $label])
                                                ->values()
                                                ->toArray();
                                        @endphp
                                        {{-- Inline-edit static multi-select combobox --}}
                                        <div
                                            x-data="architectCombobox({
                                                options:  {{ json_encode($_msData) }},
                                                multiple: true,
                                                value: (function() {
                                                    const v = inlineEdit.value;
                                                    if (!v || v === '') return [];
                                                    try { return (Array.isArray(v) ? v : JSON.parse(v)).map(String); } catch(_) { return []; }
                                                })(),
                                                onChange: function(vs) {
                                                    inlineEdit.value = JSON.stringify(vs);
                                                },
                                            })"
                                            x-init="openDropdown()"
                                            @click.outside="inlineEdit.value = JSON.stringify(_value); inlineEdit.commit($wire)"
                                            @keydown.escape.window="inlineEdit.cancel()"
                                            class="relative"
                                        >
                                            <div class="arch-combobox-trigger open flex-wrap" @click.self="openDropdown()">
                                                <template x-for="chip in selectedChips" :key="chip.id">
                                                    <span class="arch-chip">
                                                        <span x-text="chip.text" style="max-width:5rem" class="truncate"></span>
                                                        <button type="button" class="arch-chip-remove" @click.stop="removeChip(chip.id)">×</button>
                                                    </span>
                                                </template>
                                                <span x-show="!hasValue" class="arch-combobox-placeholder">{{ __('Select…') }}</span>
                                            </div>
                                            <div class="arch-combobox-dropdown" x-show="open" role="listbox">
                                                <ul x-ref="optionList" class="arch-combobox-options">
                                                    <template x-for="(opt, idx) in options" :key="opt.id">
                                                        <li class="arch-combobox-option"
                                                            :class="{ selected: isSelected(opt.id) }"
                                                            @click="select(opt.id, opt.text)" role="option">
                                                            <span class="arch-combobox-check">
                                                                <i class="fas fa-check" x-show="isSelected(opt.id)"></i>
                                                                <i class="far fa-square text-gray-300" x-show="!isSelected(opt.id)"></i>
                                                            </span>
                                                            <span x-text="opt.text"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                                <div class="p-2 border-t border-gray-100 dark:border-white/10 flex justify-end">
                                                    <button type="button" class="arch-btn arch-btn-primary arch-btn-sm"
                                                        @click="inlineEdit.value = JSON.stringify(_value); inlineEdit.commit($wire)">
                                                        {{ __('Apply') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @elseif ($colType === 'color')
                                        <div
                                            x-init="$nextTick(() => { const el = $el.querySelector('input'); if(el) el.focus(); })"
                                            @keydown.escape.window="inlineEdit.cancel()"
                                            class="flex items-center gap-2"
                                        >
                                            <input
                                                type="color"
                                                class="arch-input arch-input-color mt-inline-edit"
                                                x-model="inlineEdit.value"
                                                @change="inlineEdit.commit($wire)"
                                            >
                                            <span class="text-sm text-gray-500 dark:text-gray-400" x-text="inlineEdit.value"></span>
                                        </div>
                                        @elseif ($colType === 'icon')
                                        <div
                                            x-data="{
                                                preview() {
                                                    const v = inlineEdit.value || '';
                                                    return v.match(/^fa[srb]? /) ? v : (v ? 'fas fa-' + v : '');
                                                }
                                            }"
                                            x-init="$nextTick(() => { const el = $el.querySelector('input'); if(el){el.focus();el.select();} })"
                                            @keydown.escape.window="inlineEdit.cancel()"
                                            class="flex items-center gap-2"
                                        >
                                            <i :class="preview() || 'fas fa-question-circle text-muted'" class="mt-icon-preview"></i>
                                            <input
                                                type="text"
                                                class="mt-inline-edit"
                                                placeholder="e.g. fas fa-star"
                                                x-model="inlineEdit.value"
                                                @keydown.enter="inlineEdit.commit($wire)"
                                                @blur="inlineEdit.commitIfChangedAndActive({{ $rowId }}, '{{ $column->getKey() }}', $wire)"
                                            >
                                        </div>
                                        @elseif ($colType === 'textarea')
                                        <div
                                            x-init="$nextTick(() => { const el = $el.querySelector('textarea'); if(el){el.focus();el.select();} })"
                                            @keydown.escape.window="inlineEdit.cancel()"
                                        >
                                            <textarea
                                                class="mt-inline-edit"
                                                rows="2"
                                                x-model="inlineEdit.value"
                                                @keydown.ctrl.enter="inlineEdit.commit($wire)"
                                                @blur="inlineEdit.commitIfChangedAndActive({{ $rowId }}, '{{ $column->getKey() }}', $wire)"
                                            ></textarea>
                                        </div>
                                        @elseif (in_array($colType, ['date', 'datetime', 'time']))
                                        <div
                                            x-init="
                                                // Normalize stored datetime string to the format the native input expects:
                                                // date → YYYY-MM-DD, datetime-local → YYYY-MM-DDTHH:MM, time → HH:MM
                                                if (inlineEdit.value) {
                                                    const v = String(inlineEdit.value);
                                                    @if ($colType === 'datetime')
                                                    inlineEdit.value = v.substring(0, 16).replace(' ', 'T');
                                                    @elseif ($colType === 'time')
                                                    inlineEdit.value = v.substring(0, 5);
                                                    @else
                                                    inlineEdit.value = v.substring(0, 10);
                                                    @endif
                                                    inlineEdit.originalValue = inlineEdit.value;
                                                }
                                                $nextTick(() => { const el = $el.querySelector('input'); if(el) el.focus(); });
                                            "
                                            @keydown.escape.window="inlineEdit.cancel()"
                                        >
                                            <input
                                                type="{{ $colType === 'datetime' ? 'datetime-local' : $colType }}"
                                                class="mt-inline-edit"
                                                x-model="inlineEdit.value"
                                                @change="inlineEdit.commit($wire)"
                                                @blur="inlineEdit.cancelIfActive({{ $rowId }}, '{{ $column->getKey() }}')"
                                            >
                                        </div>
                                        @elseif ($colType === 'number')
                                        <div
                                            x-init="$nextTick(() => { const el = $el.querySelector('input'); if(el){el.focus();el.select();} })"
                                            @keydown.escape.window="inlineEdit.cancel()"
                                        >
                                            <input
                                                type="number"
                                                class="mt-inline-edit"
                                                x-model="inlineEdit.value"
                                                @keydown.enter="inlineEdit.commit($wire)"
                                                @blur="inlineEdit.commitIfChangedAndActive({{ $rowId }}, '{{ $column->getKey() }}', $wire)"
                                            >
                                        </div>
                                        @else
                                        {{-- Default: text --}}
                                        <div
                                            x-init="$nextTick(() => { const el = $el.querySelector('input'); if(el){el.focus();el.select();} })"
                                            @keydown.escape.window="inlineEdit.cancel()"
                                        >
                                            <input
                                                type="text"
                                                class="mt-inline-edit"
                                                x-model="inlineEdit.value"
                                                @keydown.enter="inlineEdit.commit($wire)"
                                                @blur="inlineEdit.commitIfChangedAndActive({{ $rowId }}, '{{ $column->getKey() }}', $wire)"
                                            >
                                        </div>
                                        @endif
                                    </template>
                                    {{-- Inline save error feedback (cell-mode) --}}
                                    <span x-show="inlineEdit.rowId === {{ $rowId }} && inlineEdit.columnKey === '{{ $column->getKey() }}' && inlineEdit.error" class="text-red-600 dark:text-red-400 text-sm" x-text="inlineEdit.error"></span>

                                    {{-- ── Row-mode input ────────────────────────────────────────
                                         Active for ALL inline-eligible cells in a row whenever the
                                         row has been opened in row-edit mode. Bound to
                                         rowEdit.values['{{ $editKey }}'] so changes feed validation
                                         on save and cascading dropdowns can react via x-effect.
                                    --}}
                                    <template x-if="rowEdit.isActive({{ $rowId }})">
                                        @if ($colType === 'checkbox')
                                        <input
                                            type="checkbox"
                                            class="arch-check-input"
                                            :checked="rowEdit.values['{{ $editKey }}'] === '1' || rowEdit.values['{{ $editKey }}'] === true || rowEdit.values['{{ $editKey }}'] === 1"
                                            @change="rowEdit.setValue('{{ $editKey }}', $event.target.checked ? '1' : '0')"
                                        >
                                        @elseif ($colType === 'select')
                                        <select
                                            class="mt-inline-edit"
                                            x-init="$el.value = rowEdit.values['{{ $editKey }}'] ?? ''"
                                            @change="rowEdit.setValue('{{ $editKey }}', $event.target.value)"
                                        >
                                            @foreach ($column->getOptions() ?? [] as $optValue => $optLabel)
                                                <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                            @endforeach
                                        </select>
                                        @elseif ($colType === 'lookup')
                                        @php $cascadeParent = $column->getCascadeFrom(); @endphp
                                        {{-- Row-mode AJAX lookup combobox. --}}
                                        <div
                                            x-data="architectCombobox({
                                                url:    '{{ $lookupUrl }}',
                                                value:  String(rowEdit.values[{{ json_encode($editKey) }}] ?? ''),
                                                onChange: function(v) {
                                                    rowEdit.setValue({{ json_encode($editKey) }}, v ?? '');
                                                    @if ($cascadeParent)
                                                    rowEdit.setValue({{ json_encode($cascadeParent) }}, null);
                                                    @endif
                                                },
                                            })"
                                            x-init="openDropdown()"
                                            @click.outside="closeDropdown()"
                                            @keydown.escape.window="closeDropdown()"
                                            class="relative"
                                        >
                                            <button type="button" class="arch-combobox-trigger open" @click="toggleDropdown()">
                                                <span x-show="hasValue" x-text="selectedLabel" class="arch-combobox-value"></span>
                                                <span x-show="!hasValue" class="arch-combobox-placeholder">{{ __('Select…') }}</span>
                                                <span class="arch-combobox-chevron"><i class="fas fa-chevron-down"></i></span>
                                            </button>
                                            <div class="arch-combobox-dropdown" x-show="open" role="listbox">
                                                <div class="arch-combobox-search-wrap">
                                                    <input x-ref="search" type="text" class="arch-combobox-search"
                                                        x-model="query" @input="onQueryInput()" placeholder="{{ __('Search…') }}" autocomplete="off">
                                                </div>
                                                <ul x-ref="optionList" class="arch-combobox-options">
                                                    <div class="arch-combobox-loading" x-show="loading"><i class="fas fa-circle-notch fa-spin text-gray-400"></i></div>
                                                    <template x-for="(opt, idx) in options" :key="opt.id">
                                                        <li class="arch-combobox-option"
                                                            :class="{ active: activeIdx===idx, selected: isSelected(opt.id) }"
                                                            @click="select(opt.id, opt.text)" role="option">
                                                            <span class="arch-combobox-check"><i class="fas fa-check" x-show="isSelected(opt.id)"></i></span>
                                                            <span x-text="opt.text"></span>
                                                        </li>
                                                    </template>
                                                    <li class="arch-combobox-empty" x-show="!loading && !options.length && query">{{ __('No results.') }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                        @elseif ($colType === 'multiselect')
                                        @php
                                            $_msData = collect($column->getOptions() ?? [])
                                                ->map(fn($label, $val) => ['id' => (string) $val, 'text' => $label])
                                                ->values()
                                                ->toArray();
                                        @endphp
                                        {{-- Row-mode static multi-select combobox --}}
                                        <div
                                            x-data="architectCombobox({
                                                options:  {{ json_encode($_msData) }},
                                                multiple: true,
                                                value: (function() {
                                                    const v = rowEdit.values[{{ json_encode($editKey) }}];
                                                    if (!v || v === '') return [];
                                                    try { return (Array.isArray(v) ? v : JSON.parse(v)).map(String); } catch(_) { return []; }
                                                })(),
                                                onChange: vs => rowEdit.setValue({{ json_encode($editKey) }}, vs),
                                            })"
                                            x-init="openDropdown()"
                                            @click.outside="closeDropdown()"
                                            class="relative"
                                        >
                                            <div class="arch-combobox-trigger open flex-wrap" @click.self="openDropdown()">
                                                <template x-for="chip in selectedChips" :key="chip.id">
                                                    <span class="arch-chip">
                                                        <span x-text="chip.text" style="max-width:5rem" class="truncate"></span>
                                                        <button type="button" class="arch-chip-remove" @click.stop="removeChip(chip.id)">×</button>
                                                    </span>
                                                </template>
                                                <span x-show="!hasValue" class="arch-combobox-placeholder">{{ __('Select…') }}</span>
                                            </div>
                                            <div class="arch-combobox-dropdown" x-show="open" role="listbox">
                                                <ul x-ref="optionList" class="arch-combobox-options">
                                                    <template x-for="(opt, idx) in options" :key="opt.id">
                                                        <li class="arch-combobox-option"
                                                            :class="{ selected: isSelected(opt.id) }"
                                                            @click="select(opt.id, opt.text)" role="option">
                                                            <span class="arch-combobox-check">
                                                                <i class="fas fa-check" x-show="isSelected(opt.id)"></i>
                                                                <i class="far fa-square text-gray-300" x-show="!isSelected(opt.id)"></i>
                                                            </span>
                                                            <span x-text="opt.text"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </div>
                                        @elseif ($colType === 'color')
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="color"
                                                class="arch-input arch-input-color mt-inline-edit"
                                                x-init="$el.value = rowEdit.values['{{ $editKey }}'] || '#000000'"
                                                @input="rowEdit.setValue('{{ $editKey }}', $event.target.value)"
                                            >
                                            <span class="text-sm text-gray-500 dark:text-gray-400" x-text="rowEdit.values['{{ $editKey }}'] || ''"></span>
                                        </div>
                                        @elseif ($colType === 'icon')
                                        <div
                                            x-data="{
                                                get _preview() {
                                                    const v = rowEdit.values['{{ $editKey }}'] || '';
                                                    return v.match(/^fa[srb]? /) ? v : (v ? 'fas fa-' + v : '');
                                                }
                                            }"
                                            class="flex items-center gap-2"
                                        >
                                            <i :class="_preview || 'fas fa-question-circle text-muted'" class="mt-icon-preview"></i>
                                            <input
                                                type="text"
                                                class="mt-inline-edit"
                                                placeholder="e.g. fas fa-star"
                                                x-init="$el.value = rowEdit.values['{{ $editKey }}'] ?? ''"
                                                @input="rowEdit.setValue('{{ $editKey }}', $event.target.value)"
                                            >
                                        </div>
                                        @elseif ($colType === 'textarea')
                                        <textarea
                                            class="mt-inline-edit"
                                            rows="2"
                                            x-init="$el.value = rowEdit.values['{{ $editKey }}'] ?? ''"
                                            @input="rowEdit.setValue('{{ $editKey }}', $event.target.value)"
                                        ></textarea>
                                        @elseif (in_array($colType, ['date', 'datetime', 'time']))
                                        <input
                                            type="{{ $colType === 'datetime' ? 'datetime-local' : $colType }}"
                                            class="mt-inline-edit"
                                            x-init="
                                                const v = rowEdit.values['{{ $editKey }}'];
                                                if (v) {
                                                    const s = String(v);
                                                    @if ($colType === 'datetime')
                                                    $el.value = s.substring(0, 16).replace(' ', 'T');
                                                    @elseif ($colType === 'time')
                                                    $el.value = s.substring(0, 5);
                                                    @else
                                                    $el.value = s.substring(0, 10);
                                                    @endif
                                                }
                                            "
                                            @change="rowEdit.setValue('{{ $editKey }}', $event.target.value)"
                                        >
                                        @elseif ($colType === 'number')
                                        <input
                                            type="number"
                                            class="mt-inline-edit"
                                            x-init="$el.value = rowEdit.values['{{ $editKey }}'] ?? ''"
                                            @input="rowEdit.setValue('{{ $editKey }}', $event.target.value)"
                                        >
                                        @else
                                        <input
                                            type="text"
                                            class="mt-inline-edit"
                                            x-init="$el.value = rowEdit.values['{{ $editKey }}'] ?? ''"
                                            @input="rowEdit.setValue('{{ $editKey }}', $event.target.value)"
                                        >
                                        @endif
                                    </template>
                                    {{-- Per-field error from row-mode validation --}}
                                    <span
                                        x-show="rowEdit.isActive({{ $rowId }}) && rowEdit.errors['{{ $editKey }}']"
                                        class="text-red-600 dark:text-red-400 text-sm block"
                                        x-text="rowEdit.errors['{{ $editKey }}']"
                                    ></span>
                                @endif
                            </td>
                        @endforeach
                        <td class="arch-row-actions text-right">
                            @php $rowIsArchived = (bool) ($row['archived'] ?? false); @endphp

                            {{-- Row-mode Save / Cancel: only visible while a row-edit is open
                                 for THIS row. Replaces the standard action buttons (which
                                 stay rendered but hidden via x-show below) so the user can
                                 commit or discard without leaving the row. --}}
                            <template x-if="rowEdit.isActive({{ $rowId }})">
                                <span class="inline-flex gap-1">
                                    <button
                                        type="button"
                                        class="arch-btn arch-btn-sm arch-btn-success"
                                        @click="rowEdit.save($wire)"
                                        title="Save row"
                                    >
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="arch-btn arch-btn-sm arch-btn-outline-secondary"
                                        @click="rowEdit.cancel()"
                                        title="Cancel"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </span>
                            </template>

                            {{-- Standard row actions: hidden while row-edit is active for this row. --}}
                            <span x-show="!rowEdit.isActive({{ $rowId }})" class="inline-flex items-center gap-1 whitespace-nowrap">
                            {{-- Custom Row Actions --}}
                            @if (! $rowIsArchived)
                                @foreach ($definition->rowActions as $rowAction)
                                    @php
                                        $canUseAction = $rowAction->getPermission() === null || app(\Entelechy\Architect\Contracts\PermissionResolver::class)->can(auth()->user(), $rowAction->getPermission());
                                        $isVisible = $rowAction->isVisibleFor($row);
                                          $rowActionIcon = $rowAction->getIcon();
                                          $rowActionUsesLegacyIcon = is_string($rowActionIcon)
                                              && (str_contains($rowActionIcon, ' ') || str_starts_with($rowActionIcon, 'fa'));
                                          $rowActionUsesBladeIcon = is_string($rowActionIcon)
                                              && ! $rowActionUsesLegacyIcon;
                                    @endphp
                                    @if ($canUseAction && $isVisible)
                                        @php
                                            $rowActionAnimation = $rowAction->getAnimation();
                                            // spin/pulse: CSS class applied statically; loading: class added at runtime by wire:loading
                                            $rowActionAnimClass = ($rowActionAnimation && $rowActionAnimation !== 'loading')
                                                ? 'arch-btn--anim-' . $rowActionAnimation
                                                : '';
                                            $rowId = (int) ($row['id'] ?? 0);
                                            $wireTarget = "handleRowAction('{$rowAction->getKey()}', {$rowId})";
                                        @endphp
                                        @if ($rowAction->getOpenInTab())
                                            {{-- ModuleTabs intent event: dispatches architect:open-record so the tab bar
                                                 opens this record in a dynamic tab. Falls back to URL navigation
                                                 when no ModuleTabsManager is present on the page. --}}
                                            @php
                                                $openInTab     = $rowAction->getOpenInTab();
                                                $tabType       = $openInTab['type'];
                                                $tabFallback   = str_replace('{id}', (string) ($row['id'] ?? ''), $openInTab['fallback']);
                                                $tabRowId      = (int) ($row['id'] ?? 0);
                                            @endphp
                                            <x-architect::button
                                                size="sm"
                                                outlined
                                                  :icon="$rowActionUsesBladeIcon ? $rowActionIcon : null"
                                                color="{{ $rowAction->getColor() }}"
                                                :tooltip="$rowAction->getLabel()"
                                                class="{{ $rowActionAnimClass }}"
                                                @click="$dispatch('architect:open-record', { type: '{{ $tabType }}', props: { id: {{ $tabRowId }} }, fallback: '{{ $tabFallback }}' })"
                                            >
                                                  @if ($rowActionUsesLegacyIcon)
                                                      <i class="{{ $rowActionIcon }}"></i>
                                                  @elseif (! $rowActionUsesBladeIcon)
                                                    {{ $rowAction->getLabel() }}
                                                @endif
                                            </x-architect::button>
                                        @elseif ($rowAction->getUrl())
                                            {{-- URL-based action (viewable, audit, etc.): render as anchor --}}
                                            <x-architect::button
                                                size="sm"
                                                outlined
                                                  :icon="$rowActionUsesBladeIcon ? $rowActionIcon : null"
                                                :href="str_replace('{id}', (string) ($row['id'] ?? ''), $rowAction->getUrl())"
                                                tag="a"
                                                :target="$rowAction->opensInNewWindow() ? '_blank' : null"
                                                :tooltip="$rowAction->getLabel()"
                                                color="{{ $rowAction->getColor() }}"
                                                class="{{ $rowActionAnimClass }}"
                                            >
                                                  @if ($rowActionUsesLegacyIcon)
                                                      <i class="{{ $rowActionIcon }}"></i>
                                                  @elseif (! $rowActionUsesBladeIcon)
                                                    {{ $rowAction->getLabel() }}
                                                @endif
                                            </x-architect::button>
                                        @else
                                            {{-- Livewire-handled action (toggle, custom, clone, etc.) --}}
                                            @if ($rowAction->getConfirm())
                                                @if ($rowActionAnimation === 'loading')
                                                    <x-architect::button
                                                        size="sm"
                                                        outlined
                                                          :icon="$rowActionUsesBladeIcon ? $rowActionIcon : null"
                                                        color="{{ $rowAction->getColor() }}"
                                                        wire:click="{{ $wireTarget }}"
                                                        wire:confirm="{{ $rowAction->getConfirm() }}"
                                                        :tooltip="$rowAction->getLabel()"
                                                        wire:loading.class="arch-btn--loading"
                                                        wire:loading.attr="disabled"
                                                        wire:target="{{ $wireTarget }}"
                                                    >
                                                          @if ($rowActionUsesLegacyIcon)
                                                              <i class="{{ $rowActionIcon }}"></i>
                                                          @elseif (! $rowActionUsesBladeIcon)
                                                            {{ $rowAction->getLabel() }}
                                                        @endif
                                                    </x-architect::button>
                                                @else
                                                    <x-architect::button
                                                        size="sm"
                                                        outlined
                                                          :icon="$rowActionUsesBladeIcon ? $rowActionIcon : null"
                                                        color="{{ $rowAction->getColor() }}"
                                                        wire:click="{{ $wireTarget }}"
                                                        wire:confirm="{{ $rowAction->getConfirm() }}"
                                                        :tooltip="$rowAction->getLabel()"
                                                        class="{{ $rowActionAnimClass }}"
                                                    >
                                                          @if ($rowActionUsesLegacyIcon)
                                                              <i class="{{ $rowActionIcon }}"></i>
                                                          @elseif (! $rowActionUsesBladeIcon)
                                                            {{ $rowAction->getLabel() }}
                                                        @endif
                                                    </x-architect::button>
                                                @endif
                                            @else
                                                @if ($rowActionAnimation === 'loading')
                                                    <x-architect::button
                                                        size="sm"
                                                        outlined
                                                          :icon="$rowActionUsesBladeIcon ? $rowActionIcon : null"
                                                        color="{{ $rowAction->getColor() }}"
                                                        wire:click="{{ $wireTarget }}"
                                                        :tooltip="$rowAction->getLabel()"
                                                        wire:loading.class="arch-btn--loading"
                                                        wire:loading.attr="disabled"
                                                        wire:target="{{ $wireTarget }}"
                                                    >
                                                          @if ($rowActionUsesLegacyIcon)
                                                              <i class="{{ $rowActionIcon }}"></i>
                                                          @elseif (! $rowActionUsesBladeIcon)
                                                            {{ $rowAction->getLabel() }}
                                                        @endif
                                                    </x-architect::button>
                                                @else
                                                    <x-architect::button
                                                        size="sm"
                                                        outlined
                                                          :icon="$rowActionUsesBladeIcon ? $rowActionIcon : null"
                                                        color="{{ $rowAction->getColor() }}"
                                                        wire:click="{{ $wireTarget }}"
                                                        :tooltip="$rowAction->getLabel()"
                                                        class="{{ $rowActionAnimClass }}"
                                                    >
                                                          @if ($rowActionUsesLegacyIcon)
                                                              <i class="{{ $rowActionIcon }}"></i>
                                                          @elseif (! $rowActionUsesBladeIcon)
                                                            {{ $rowAction->getLabel() }}
                                                        @endif
                                                    </x-architect::button>
                                                @endif
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            @endif

                            {{-- Custom Row Actions (class-based, server-executed via handle()) --}}
                            @if (! $rowIsArchived)
                                @foreach ($definition->customRowActions as $customRowAction)
                                    @php
                                        $customRowActionNode = $customRowAction->permissionNode() ?? $definition->permissions->modify;
                                        $canUseCustomRowAction = app(\Entelechy\Architect\Contracts\PermissionResolver::class)->can(auth()->user(), $customRowActionNode);
                                        $customRowActionIsVisible = $customRowAction->isVisibleFor($row);
                                        $customRowActionIcon = $customRowAction->icon();
                                        $customRowActionUsesLegacyIcon = is_string($customRowActionIcon)
                                            && (str_contains($customRowActionIcon, ' ') || str_starts_with($customRowActionIcon, 'fa'));
                                        $customRowActionUsesBladeIcon = is_string($customRowActionIcon)
                                            && ! $customRowActionUsesLegacyIcon;
                                    @endphp
                                    @if ($canUseCustomRowAction && $customRowActionIsVisible)
                                        @php
                                            $customRowId = (int) ($row['id'] ?? 0);
                                            $customWireTarget = "handleCustomRowAction('{$customRowAction->getKey()}', {$customRowId})";
                                        @endphp
                                        @if ($customRowAction->confirm())
                                            <x-architect::button
                                                size="sm"
                                                outlined
                                                :icon="$customRowActionUsesBladeIcon ? $customRowActionIcon : null"
                                                color="{{ $customRowAction->color() }}"
                                                wire:click="{{ $customWireTarget }}"
                                                wire:confirm="{{ $customRowAction->confirm() }}"
                                                :tooltip="$customRowAction->getLabel()"
                                            >
                                                @if ($customRowActionUsesLegacyIcon)
                                                    <i class="{{ $customRowActionIcon }}"></i>
                                                @elseif (! $customRowActionUsesBladeIcon)
                                                    {{ $customRowAction->getLabel() }}
                                                @endif
                                            </x-architect::button>
                                        @else
                                            <x-architect::button
                                                size="sm"
                                                outlined
                                                :icon="$customRowActionUsesBladeIcon ? $customRowActionIcon : null"
                                                color="{{ $customRowAction->color() }}"
                                                wire:click="{{ $customWireTarget }}"
                                                :tooltip="$customRowAction->getLabel()"
                                            >
                                                @if ($customRowActionUsesLegacyIcon)
                                                    <i class="{{ $customRowActionIcon }}"></i>
                                                @elseif (! $customRowActionUsesBladeIcon)
                                                    {{ $customRowAction->getLabel() }}
                                                @endif
                                            </x-architect::button>
                                        @endif
                                    @endif
                                @endforeach
                            @endif

                            {{-- Standard Edit Button --}}
                            @if (! $rowIsArchived && $definition->modifiable)
                                @if ($definition->customModifyForm)
                                    @php
                                        $customModify = $definition->customModifyForm;
                                        $rowId = (int) ($row['id'] ?? 0);
                                        $customModifyUrl = str_replace('{id}', (string) $rowId, $customModify->url ?? '');
                                    @endphp

                                    @if ($customModify->mode === 'tabs-manager')
                                        <x-architect::button
                                            size="sm"
                                            outlined
                                            icon="heroicon-m-pencil-square"
                                            color="primary"
                                            @click="$dispatch('architect:open-record', { type: '{{ $customModify->tabType }}', props: { id: {{ $rowId }} }, fallback: '{{ $customModifyUrl }}' })"
                                            tooltip="Edit"
                                        />
                                    @elseif ($customModify->mode === 'same-window-page')
                                        <x-architect::button
                                            size="sm"
                                            outlined
                                            icon="heroicon-m-pencil-square"
                                            color="primary"
                                            @click="window.location.href='{{ $customModifyUrl }}'"
                                            tooltip="Edit"
                                        />
                                    @elseif ($customModify->mode === 'new-window')
                                        <x-architect::button
                                            size="sm"
                                            outlined
                                            icon="heroicon-m-pencil-square"
                                            color="primary"
                                            @click="(() => {
                                                const _u = new URL('{{ $customModifyUrl }}', window.location.origin);
                                                const _return = new URL(window.location.href);
                                                _return.searchParams.set('{{ $customModify->callbackQueryKey ?? 'architect_refresh' }}', '{{ $instanceKey }}');
                                                _u.searchParams.set('architect_table_instance', '{{ $instanceKey }}');
                                                _u.searchParams.set('architect_table_refresh_key', '{{ $customModify->callbackQueryKey ?? 'architect_refresh' }}');
                                                _u.searchParams.set('architect_table_return_url', _return.toString());
                                                window.open(_u.toString(), '_blank', 'noopener');
                                            })()"
                                            tooltip="Edit"
                                        />
                                    @else
                                        <x-architect::button
                                            size="sm"
                                            outlined
                                            icon="heroicon-m-pencil-square"
                                            color="primary"
                                            @click="$dispatch('architect:open-custom-form', {
                                                definitionClass: '{{ addslashes($definitionClass) }}',
                                                title: 'Edit {{ addslashes($definition->title ?? 'Record') }}',
                                                customDefinitionClass: '{{ addslashes($customModify->definitionClass) }}',
                                                customMode: '{{ $customModify->mode }}',
                                                recordId: {{ $rowId }}
                                            })"
                                            tooltip="Edit"
                                        />
                                    @endif
                                @elseif ($definition->modifyOpenInTab && $definition->modifyTabType)
                                    @php
                                        $_modifyFallback = str_replace('{id}', (string) ($row['id'] ?? ''), $definition->modifyUrl ?? '');
                                    @endphp
                                    <x-architect::button
                                        size="sm"
                                        outlined
                                        icon="heroicon-m-pencil-square"
                                        color="primary"
                                        @click="$dispatch('architect:open-record', { type: '{{ $definition->modifyTabType }}', props: { id: {{ (int) ($row['id'] ?? 0) }} }, fallback: '{{ $_modifyFallback }}' })"
                                        tooltip="Edit"
                                    />
                                @else
                                    <x-architect::button
                                        size="sm"
                                        outlined
                                        icon="heroicon-m-pencil-square"
                                        color="primary"
                                        @click="$dispatch('architect:open-edit', { definitionClass: '{{ addslashes($definitionClass) }}', id: {{ (int) ($row['id'] ?? 0) }} })"
                                        tooltip="Edit"
                                    />
                                @endif
                            @endif

                            {{-- Archive / Unarchive Buttons --}}
                            @if ($definition->archivable)
                                @if ($rowIsArchived && $definition->allowUnarchive)
                                    <x-architect::button
                                        size="sm"
                                        outlined
                                        color="success"
                                        icon="heroicon-m-arrow-uturn-left"
                                        wire:click="restore({{ (int) ($row['id'] ?? 0) }})"
                                        wire:confirm="Unarchive this record?"
                                        tooltip="Unarchive"
                                    />
                                @elseif (! $rowIsArchived)
                                    <x-architect::button
                                        size="sm"
                                        outlined
                                        color="warning"
                                        icon="heroicon-m-archive-box"
                                        wire:click="confirmArchive({{ (int) ($row['id'] ?? 0) }}, '{{ addslashes($row['name'] ?? $row['title'] ?? '') }}')"
                                        tooltip="Archive"
                                    />
                                @endif
                            @endif

                            {{-- Delete Button: active records (requires deletable()) --}}
                            @if (! $rowIsArchived && $definition->deletable)
                                <x-architect::button
                                    size="sm"
                                    outlined
                                    color="danger"
                                    icon="heroicon-m-trash"
                                    wire:click="confirmDelete({{ (int) ($row['id'] ?? 0) }}, '{{ addslashes($row['name'] ?? $row['title'] ?? '') }}')"
                                    tooltip="Delete permanently"
                                />
                            @endif

                            {{-- Delete Button: archived records (requires allowDelete on archivable()) --}}
                            @if ($rowIsArchived && $definition->archivable && $definition->allowDelete)
                                <x-architect::button
                                    size="sm"
                                    outlined
                                    color="danger"
                                    icon="heroicon-m-trash"
                                    wire:click="confirmDelete({{ (int) ($row['id'] ?? 0) }}, '{{ addslashes($row['name'] ?? $row['title'] ?? '') }}')"
                                    tooltip="Delete permanently"
                                />
                            @endif

                            @if (! $rowIsArchived
                                && count($definition->rowActions) === 0
                                && ! $definition->modifiable
                                && ! $definition->archivable)
                                <span class="text-gray-500 dark:text-gray-400 text-sm">—</span>
                            @endif
                            </span>{{-- /x-show: standard row actions --}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 + ($definition->selectableRows ? 1 : 0) }}">
                            <x-architect::empty-state :title="__('No matching records.')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Bulk action bar — confined to the table body, sticky to viewport bottom --}}
    @if ($definition->selectableRows && count($selected) > 0)
        @php
        // Build a TSV-ready clipboard payload for the selected rows visible on
        // the current page. Only display columns (already visibility-filtered)
        // are included — edit keys and the primary key are excluded.
        $_copyHeadings = [];
        $_copyRowsData = [];
        foreach ($columns as $_cc) {
            $_copyHeadings[] = $_cc->getLabel();
        }
        foreach ($rows as $_cr) {
            if (! in_array((int) ($_cr['id'] ?? 0), $selected, true)) {
                continue;
            }
            $_rowVals = [];
            foreach ($columns as $_cc) {
                $_val = $_cr[$_cc->getKey()] ?? '';
                if ($_cc->isToggleable()) {
                    $_val = $_val ? $_cc->getToggleOnLabel() : $_cc->getToggleOffLabel();
                } elseif (is_bool($_val)) {
                    $_val = $_val ? 'Yes' : 'No';
                } elseif (is_array($_val)) {
                    $_val = implode(', ', $_val);
                }
                $_rowVals[] = (string) $_val;
            }
            $_copyRowsData[] = $_rowVals;
        }
        $_visibleSelectedCount = count($_copyRowsData);
        $_copyPayloadJson = json_encode(
            ['headings' => $_copyHeadings, 'rows' => $_copyRowsData],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    @endphp
        <div class="sticky bottom-4 z-10 flex justify-center pointer-events-none mb-4">
            <div class="arch-bulk-bar">
            <div
                class="arch-card-body flex flex-wrap gap-2 items-center py-2"
                x-data="{
                    _d: null,
                    _copied: null,
                    init() { this._d = JSON.parse(this.$el.dataset.copyPayload); },
                    copyRows(withHeadings) {
                        if (!this._d || !this._d.rows.length) return;
                        const lines = withHeadings
                            ? [this._d.headings, ...this._d.rows]
                            : this._d.rows;
                        const tsv = lines.map(r => r.join('\t')).join('\n');
                        navigator.clipboard.writeText(tsv).then(() => {
                            this._copied = withHeadings ? 'h' : 'd';
                            setTimeout(() => { this._copied = null; }, 2000);
                        });
                    },
                }"
                data-copy-payload='{!! $_copyPayloadJson !!}'
            >
                <span class="arch-badge arch-badge-primary-lt ml-2">{{ count($selected) }} selected</span>

                @foreach ($definition->bulkActions as $action)
                    @if ($action->requiresReason())
                        {{-- Reason required: button fires bulkAction() which opens the generic modal --}}
                        <button
                            type="button"
                            class="arch-btn arch-btn-sm arch-btn-outline-{{ $action->color() }}"
                            wire:click="bulkAction('{{ $action->getKey() }}')"
                            title="{{ $action->getLabel() }}"
                        >
                            @if ($action->icon())
                                <i class="fas fa-{{ $action->icon() }} ml-1"></i>
                            @endif
                            {{ $action->getLabel() }}
                        </button>
                    @elseif ($action->confirm())
                        <button
                            type="button"
                            class="arch-btn arch-btn-sm arch-btn-outline-{{ $action->color() }}"
                            wire:click="bulkAction('{{ $action->getKey() }}')"
                            wire:confirm="{{ $action->confirm() }}"
                            title="{{ $action->getLabel() }}"
                        >
                            @if ($action->icon())
                                <i class="fas fa-{{ $action->icon() }} ml-1"></i>
                            @endif
                            {{ $action->getLabel() }}
                        </button>
                    @else
                        <button
                            type="button"
                            class="arch-btn arch-btn-sm arch-btn-outline-{{ $action->color() }}"
                            wire:click="bulkAction('{{ $action->getKey() }}')"
                            title="{{ $action->getLabel() }}"
                        >
                            @if ($action->icon())
                                <i class="fas fa-{{ $action->icon() }} ml-1"></i>
                            @endif
                            {{ $action->getLabel() }}
                        </button>
                    @endif
                @endforeach

                {{-- Clipboard copy — pastes as TSV, compatible with spreadsheets. --}}
                <div class="vr mx-1"></div>
                <button
                    type="button"
                    class="arch-btn arch-btn-sm arch-btn-outline-secondary"
                    @click="copyRows(false)"
                    @disabled($_visibleSelectedCount === 0)
                    title="{{ $_visibleSelectedCount > 0
                        ? 'Copy ' . $_visibleSelectedCount . ' row' . ($_visibleSelectedCount === 1 ? '' : 's') . ' as tab-separated text'
                        : 'No selected rows are visible on this page' }}"
                >
                    <i class="fas fa-copy ml-1"></i>
                    <span x-text="_copied === 'd' ? 'Copied!' : 'Copy'">Copy</span>
                </button>
                <button
                    type="button"
                    class="arch-btn arch-btn-sm arch-btn-outline-secondary"
                    @click="copyRows(true)"
                    @disabled($_visibleSelectedCount === 0)
                    title="{{ $_visibleSelectedCount > 0
                        ? 'Copy ' . $_visibleSelectedCount . ' row' . ($_visibleSelectedCount === 1 ? '' : 's') . ' with column headings'
                        : 'No selected rows are visible on this page' }}"
                >
                    <i class="fas fa-table ml-1"></i>
                    <span x-text="_copied === 'h' ? 'Copied!' : 'Copy (with headings)'">Copy (with headings)</span>
                </button>

                <button
                    type="button"
                    class="arch-btn arch-btn-sm arch-btn-outline-secondary mr-2"
                    wire:click="clearSelection"
                    title="Clear selection"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            </div>
        </div>
    @endif

    @if ($definition->isPaginated)
    <div class="arch-card-footer">
        {{--
            Filament's pagination component handles everything natively:
            [Showing X–Y of Z]  [Per page: 25▾]  [1 2 3 4]
            At narrow viewports it collapses to [< Prev] [25▾] [Next >].
        --}}
        @php
            $_pageOptions = $definition->perPageOptions !== [] ? $definition->perPageOptions : [];
        @endphp
        @if ($_pageOptions !== [])
            <x-architect::pagination
                :paginator="$paginator"
                :page-options="$_pageOptions"
                current-page-option-property="perPage"
            />
        @else
            <x-architect::pagination :paginator="$paginator" />
        @endif
    </div>
    @endif
</div>
</x-architect::table.shell>

@if ($bulkMessage)
    <div class="arch-alert arch-alert-success mt-2" role="alert" wire:key="bulk-msg">{{ $bulkMessage }}</div>
@endif
@if ($bulkError)
    <div class="arch-alert arch-alert-danger mt-2" role="alert" wire:key="bulk-err">{{ $bulkError }}</div>
@endif
@if ($rowActionMessage)
    <div class="arch-alert arch-alert-success mt-2" role="alert" wire:key="row-action-msg">{{ $rowActionMessage }}</div>
@endif
@if ($rowActionError)
    <div class="arch-alert arch-alert-danger mt-2" role="alert" wire:key="row-action-err">{{ $rowActionError }}</div>
@endif

{{-- ── Archive confirmation dialog ────────────────────────────────────────── --}}
@if ($definition->archivable && $pendingArchiveId !== null)
    <div class="arch-dialog-backdrop" aria-hidden="true"></div>
    <div class="arch-dialog-wrap" role="dialog" aria-modal="true" aria-labelledby="arch-archive-dialog-title">
        <div class="arch-dialog" @keydown.escape.window="$wire.call('cancelArchive')">
            <div class="arch-dialog-header">
                <h3 class="arch-dialog-title" id="arch-archive-dialog-title">
                    <i class="fas fa-archive text-amber-500 mr-2"></i>Archive record
                </h3>
                <button type="button" class="arch-btn-close" wire:click="cancelArchive" aria-label="Close"></button>
            </div>
            <div class="arch-dialog-body">
                @if ($definition->archivablePhraseRequired && $pendingArchiveRequiredPhrase !== null)
                    <div class="mb-4">
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                            Type <strong class="text-gray-700 dark:text-gray-200">{{ $pendingArchiveRequiredPhrase }}</strong> to confirm archiving this record.
                        </p>
                        <input
                            type="text"
                            class="arch-input"
                            wire:model="archivePhraseInput"
                            placeholder="{{ $pendingArchiveRequiredPhrase }}"
                            autocomplete="off"
                            autofocus
                        />
                    </div>
                @endif
                @if ($definition->requiresDeletionReason)
                    <div class="mb-2">
                        <label class="arch-label" for="module-table-archive-reason">Reason</label>
                        <textarea
                            id="module-table-archive-reason"
                            class="arch-input"
                            wire:model="archiveReason"
                            rows="3"
                            maxlength="500"
                            required
                            {{ !$definition->archivablePhraseRequired ? 'autofocus' : '' }}
                        ></textarea>
                    </div>
                @endif
                @if (!$definition->requiresDeletionReason && !$definition->archivablePhraseRequired)
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        Archive this {{ $definition->title }} entry? It can be restored later.
                    </p>
                @endif
                @if ($archiveError)
                    <div class="arch-alert arch-alert-danger" role="alert">{{ $archiveError }}</div>
                @endif
            </div>
            <div class="arch-dialog-footer">
                <button type="button" class="arch-btn arch-btn-secondary arch-btn-sm" wire:click="cancelArchive">
                    Cancel
                </button>
                <button type="button" class="arch-btn arch-btn-warning arch-btn-sm" wire:click="submitArchive">
                    <i class="fas fa-archive"></i>Archive
                </button>
            </div>
        </div>
    </div>
@endif

{{-- ── Delete confirmation dialog ─────────────────────────────────────────── --}}
@if ($definition->deletable && $pendingDeleteId !== null)
    <div class="arch-dialog-backdrop" aria-hidden="true"></div>
    <div class="arch-dialog-wrap" role="dialog" aria-modal="true" aria-labelledby="arch-delete-dialog-title">
        <div class="arch-dialog" @keydown.escape.window="$wire.call('cancelDelete')">
            <div class="arch-dialog-header">
                <h3 class="arch-dialog-title" id="arch-delete-dialog-title">
                    <i class="fas fa-trash text-red-500 mr-2"></i>Delete record permanently
                </h3>
                <button type="button" class="arch-btn-close" wire:click="cancelDelete" aria-label="Close"></button>
            </div>
            <div class="arch-dialog-body">
                <div class="arch-alert arch-alert-danger mb-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><strong>Warning:</strong> This action cannot be undone.</span>
                </div>
                @if ($definition->deletablePhraseRequired && $pendingDeleteRequiredPhrase !== null)
                    <div class="mb-4">
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                            Type <strong class="text-gray-700 dark:text-gray-200">{{ $pendingDeleteRequiredPhrase }}</strong> to confirm permanent deletion.
                        </p>
                        <input
                            type="text"
                            class="arch-input"
                            wire:model="deletePhraseInput"
                            placeholder="{{ $pendingDeleteRequiredPhrase }}"
                            autocomplete="off"
                            autofocus
                        />
                    </div>
                @endif
                @if ($definition->deletableReasonRequired)
                    <div class="mb-2">
                        <label class="arch-label" for="module-table-delete-reason">Reason</label>
                        <textarea
                            id="module-table-delete-reason"
                            class="arch-input"
                            wire:model="deleteReason"
                            rows="3"
                            maxlength="500"
                            required
                            {{ !$definition->deletablePhraseRequired ? 'autofocus' : '' }}
                        ></textarea>
                    </div>
                @endif
                @if (!$definition->deletableReasonRequired && !$definition->deletablePhraseRequired)
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        Are you sure you want to permanently delete this {{ $definition->title }} entry?
                    </p>
                @endif
                @if ($deleteError)
                    <div class="arch-alert arch-alert-danger" role="alert">{{ $deleteError }}</div>
                @endif
            </div>
            <div class="arch-dialog-footer">
                <button type="button" class="arch-btn arch-btn-secondary arch-btn-sm" wire:click="cancelDelete">
                    Cancel
                </button>
                <button type="button" class="arch-btn arch-btn-danger arch-btn-sm" wire:click="submitDelete">
                    <i class="fas fa-trash"></i>Delete permanently
                </button>
            </div>
        </div>
    </div>
@endif

{{-- ── Generic bulk action reason dialog ──────────────────────────────────── --}}
@if ($pendingBulkActionKey !== null)
    @php
        $pendingAction = null;
        foreach ($definition->bulkActions as $_ba) {
            if ($_ba->getKey() === $pendingBulkActionKey) {
                $pendingAction = $_ba;
                break;
            }
        }
    @endphp
    @if ($pendingAction)
    <div class="arch-dialog-backdrop" aria-hidden="true"></div>
    <div class="arch-dialog-wrap" role="dialog" aria-modal="true" aria-labelledby="arch-bulk-reason-dialog-title">
        <div class="arch-dialog" @keydown.escape.window="$wire.call('cancelPendingBulkAction')">
            <div class="arch-dialog-header">
                <h3 class="arch-dialog-title" id="arch-bulk-reason-dialog-title">
                    @if ($pendingAction->icon())
                        <i class="fas fa-{{ $pendingAction->icon() }} mr-2"></i>
                    @endif
                    {{ $pendingAction->getLabel() }} {{ count($selected) }} record(s)
                </h3>
                <button type="button" class="arch-btn-close" wire:click="cancelPendingBulkAction" aria-label="Close"></button>
            </div>
            <div class="arch-dialog-body">
                @if ($pendingBulkRequiredPhrase !== null)
                    <div class="mb-4">
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                            Type <strong class="text-gray-700 dark:text-gray-200">{{ $pendingBulkRequiredPhrase }}</strong> to confirm this action on {{ count($selected) }} record(s).
                        </p>
                        <input
                            type="text"
                            class="arch-input"
                            wire:model="bulkPhraseInput"
                            placeholder="{{ $pendingBulkRequiredPhrase }}"
                            autocomplete="off"
                            {{ !$pendingAction->requiresReason() ? 'autofocus' : '' }}
                        />
                    </div>
                @endif
                @if ($pendingAction->requiresReason())
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">
                        Please record a reason for this action on {{ count($selected) }} record(s).
                        The reason is preserved for audit purposes.
                    </p>
                    <div class="mb-2">
                        <label class="arch-label" for="arch-bulk-reason-input">Reason</label>
                        <textarea
                            id="arch-bulk-reason-input"
                            class="arch-input"
                            wire:model="bulkActionReason"
                            rows="3"
                            maxlength="500"
                            required
                            {{ !$pendingAction->requiresPhrase() ? 'autofocus' : '' }}
                        ></textarea>
                    </div>
                @endif
                @if ($bulkActionError)
                    <div class="arch-alert arch-alert-danger" role="alert">{{ $bulkActionError }}</div>
                @endif
            </div>
            <div class="arch-dialog-footer">
                <button type="button" class="arch-btn arch-btn-secondary arch-btn-sm" wire:click="cancelPendingBulkAction">
                    Cancel
                </button>
                <button type="button" class="arch-btn arch-btn-{{ $pendingAction->color() }} arch-btn-sm" wire:click="submitPendingBulkAction">
                    @if ($pendingAction->icon())
                        <i class="fas fa-{{ $pendingAction->icon() }}"></i>
                    @endif
                    {{ $pendingAction->getLabel() }} {{ count($selected) }} record(s)
                </button>
            </div>
        </div>
    </div>
    @endif
@endif

{{-- Form panel (slide-over offcanvas) lives outside the card so the
     backdrop can occlude the whole page. --}}
@livewire('architect-form-panel', ['definitionClass' => $definitionClass, 'embedded' => $embedded], key('form-panel-' . md5($definitionClass)))

{{-- CSV Import wizard (modal) — mounted once per importable table.
     The wizard listens for `architect:open-import` and reads the
     definition class from the event payload, so we don't need to
     pass it as a prop. --}}
@if ($definition->importDefinition)
    @livewire('architect-import-wizard', [], key('import-wizard-' . md5($definitionClass)))
@endif

{{-- ── Filter slide-over panel (Alpine-driven) ─────────────────────────── --}}
{{-- wire:ignore prevents Livewire morphdom from patching the <template> on every
     round-trip. Alpine's x-teleport + filtersOpen state drives show/hide.      --}}
@if (count($definition->filters) > 0 || $definition->archivable)
    @if ($embedded)
        <div id="arch-filter-portal-{{ $instanceKey }}" wire:ignore class="absolute inset-0 pointer-events-none"></div>
    @endif
    <div wire:ignore style="display:none">
        {{--
            x-teleport MUST have a single root element — some Alpine versions
            only connect the scope to the first root sibling.
            We wrap backdrop + panel in one <div> (no styles) so both live
            under a single Alpine-managed root. Fixed positioning inside
            means the wrapper div has zero visual effect.

            When embedded (e.g. rendered inside a live preview surface),
            teleport to a local portal div instead of <body> so the panel
            stays confined to the embedding container. The --embedded CSS
            modifier classes then switch the panel from fixed to absolute
            positioning relative to that portal's positioned ancestor.
        --}}
        <template x-teleport="{{ $embedded ? ('#arch-filter-portal-'.$instanceKey) : 'body' }}">
          <div>
            {{-- Backdrop: simple Alpine fade (opacity 0→1) --}}
            <div
                @class(['arch-slide-over-backdrop', 'arch-slide-over-backdrop--embedded' => $embedded, 'pointer-events-auto' => $embedded])
                x-show="filtersOpen"
                x-transition
                @click="closeFilters()"
                aria-hidden="true"
            ></div>

            {{-- Panel: appear/disappear; CSS animation on .arch-slide-over handles the slide --}}
            <div
                @class(['arch-slide-over', 'arch-slide-over--embedded' => $embedded, 'pointer-events-auto' => $embedded])
                id="arch-filter-panel-{{ $instanceKey }}"
                x-show="filtersOpen"
                x-transition
                @keydown.escape.window="closeFilters()"
                role="dialog"
                aria-modal="true"
                aria-label="Filters"
            >
                <div class="arch-slide-over-header">
                    <span class="arch-slide-over-title">
                        <i class="fas fa-filter text-gray-400"></i>Filters
                    </span>
                    <button type="button" class="arch-btn-close" @click="closeFilters()" aria-label="Close filters"></button>
                </div>

                <div class="arch-slide-over-body">
                    {{-- Bookmarked Filters section --}}
                    @if ($definition->filterBookmarkFilters)
                    <div x-show="bookmarkedFilters.length > 0" x-cloak>
                        <div class="mb-3">
                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2 uppercase font-semibold mt-bookmark-label">Bookmarked filters</p>
                            <select id="mt-bookmarked-filters-select-{{ md5($definitionClass) }}"
                                class="arch-select arch-select-sm w-full"
                                aria-label="{{ __('Bookmarked filters') }}"
                            ></select>
                        </div>
                        <hr class="my-3">
                    </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        @foreach ($definition->filters as $filter)
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="arch-label mb-0">{{ $filter->getLabel() }}</label>
                                    <button
                                        type="button"
                                        x-show="$wire.filters['{{ $filter->name() }}']"
                                        class="arch-btn arch-btn-sm arch-btn-link text-gray-500 dark:text-gray-400 p-0 lh-1"
                                        @click="$wire.call('setFilter', '{{ $filter->name() }}', '')"
                                        title="Clear this filter"
                                        aria-label="Clear {{ $filter->getLabel() }} filter"
                                    ><i class="fas fa-times"></i></button>
                                </div>
                                @php($renderer = $filter->renderer())
                                @if (is_string($renderer))
                                    @include($renderer, ['filter' => $filter])
                                @else
                                    {!! $renderer->render() !!}
                                @endif
                            </div>
                        @endforeach
                    </div>

                </div>{{-- /arch-slide-over-body --}}

                <div class="arch-slide-over-footer">
                    @if ($definition->filterPersistence)
                    <div class="arch-check arch-switch mb-2">
                        <input
                            class="arch-switch-input"
                            type="checkbox"
                            role="switch"
                            id="mt-filter-persist-{{ md5($definitionClass) }}"
                            :checked="persistEnabled"
                            @change="togglePersist()"
                        >
                        <label
                            class="arch-check-label text-sm text-gray-500 dark:text-gray-400"
                            for="mt-filter-persist-{{ md5($definitionClass) }}"
                        >Remember filters</label>
                    </div>
                    @endif
                    @if ($definition->archivable)
                    <div class="arch-check arch-switch mb-3">
                        <input
                            class="arch-switch-input"
                            type="checkbox"
                            role="switch"
                            id="mt-include-archived-{{ md5($definitionClass) }}"
                            wire:model.live="includeArchived"
                        >
                        <label
                            class="arch-check-label text-sm text-gray-500 dark:text-gray-400"
                            for="mt-include-archived-{{ md5($definitionClass) }}"
                        >Include archived records</label>
                    </div>
                    @endif
                    <div class="flex gap-2">
                        @if ($definition->filterBookmarkFilters)
                        <button
                            type="button"
                            class="arch-btn arch-btn-outline-success arch-btn-sm"
                            x-show="Object.keys($wire.filters).length > 0"
                            @click="bookmarkCurrentFilter()"
                            title="Bookmark current filters"
                        ><i class="fas fa-bookmark"></i></button>
                        @endif
                        <button
                            type="button"
                            class="arch-btn arch-btn-danger arch-btn-sm flex-1"
                            @click="closeFilters(); Object.keys($wire.filters).length > 0 && $wire.call('clearFilters')"
                        ><i class="fas fa-times"></i>Clear &amp; Close</button>
                        <button
                            type="button"
                            class="arch-btn arch-btn-secondary arch-btn-sm"
                            @click="closeFilters()"
                        ><i class="fas fa-check"></i>Done</button>
                    </div>
                </div>
            </div>{{-- /arch-slide-over --}}
          </div>{{-- /x-teleport single root wrapper --}}
        </template>
    </div>
@endif

{{-- Navigator (position = bottom, rendered after the filter panel) --}}
@if ($definition->navigator && $definition->navigator->position === 'bottom')
    <x-architect::definition-renderer :definition="$definition->navigator" />
@endif

</div>
