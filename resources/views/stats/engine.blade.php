{{--
    Architect Stats Dashboard Engine.

    Full-page view for DashboardEngine. Renders the date filter controls,
    optional granularity toggle and export button, then loops over the resolved
    sections dispatching to the appropriate partial.

    Variables:
        $definition        ArchitectStatDefinition (type = 'dashboard')
        $resolvedSections  array — parallel to $definition->sections with resolved data
        $range             DateRange
        $granularity       string — H | D | M | A
        $dateFrom          string — Y-m-d
        $dateTo            string — Y-m-d
        $hasChart          bool — whether any section is a chart
--}}
@php
    use Illuminate\Support\Str;
    $dashboardKey = Str::slug($definition->pageTitle ?? class_basename($definitionClass));
    $sectionsMeta = collect($definition->sections)
        ->values()
        ->map(fn ($s, $i) => [
            'key'         => $s->key ?? "section-{$i}",
            'title'       => $s->title ?? 'Section ' . ($i + 1),
            'defaultSpan' => $definition->sectionSpans[$i] ?? 12,
        ])
        ->all();
@endphp

<div
    data-loading="{{ $isLoading ? 'true' : 'false' }}"
    x-data="dashboardEdit({ dashboardKey: @js($dashboardKey), sections: @js($sectionsMeta) })"
    x-cloak
>

    @if ($hasError)
        <x-architect::callout type="danger" class="mb-4">{{ $errorMessage }}</x-architect::callout>
    @endif

    {{-- ── Controls bar ────────────────────────────────────────────────── --}}
    {{-- overflow: visible overrides the card's overflow: clip so that the absolute-
         positioned dropdown panels can extend below the card boundary. --}}
    <div class="arch-card mb-4" style="overflow: visible;">
        <div class="arch-card-body py-3">

            {{-- Normal mode --}}
            <div class="flex flex-wrap items-center gap-3" x-show="!editMode">

                {{-- Date range picker --}}
                @include('architect::stats.partials.date-filter', [
                    'dateFrom' => $dateFrom,
                    'dateTo'   => $dateTo,
                ])

                {{-- Granularity toggle --}}
                @if ($hasChart)
                    <div class="flex items-center gap-1 ml-auto">
                        <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Period:</span>
                        @foreach (['H' => 'Hourly', 'D' => 'Daily', 'M' => 'Monthly', 'A' => 'Annually'] as $gKey => $label)
                            <button
                                type="button"
                                wire:click="updateGranularity('{{ $gKey }}')"
                                class="arch-btn arch-btn-xs {{ $granularity === $gKey ? 'arch-btn-secondary' : 'arch-btn-outline-secondary' }}"
                            >{{ $label }}</button>
                        @endforeach
                    </div>
                @endif

                {{-- Export button --}}
                @if ($definition->exportEnabled)
                    <button
                        type="button"
                        @click="$wire.export(visibleKeys)"
                        wire:loading.attr="disabled"
                        class="arch-btn arch-btn-outline-secondary arch-btn-sm {{ $hasChart ? '' : 'ml-auto' }}"
                    >
                        <i class="fas fa-file-excel mr-1.5"></i>
                        <span wire:loading.remove wire:target="export">Export</span>
                        <span wire:loading wire:target="export">Exporting…</span>
                    </button>
                @endif

                {{-- Customise button --}}
                <button
                    type="button"
                    @click="toggleEdit()"
                    class="arch-btn arch-btn-outline-secondary arch-btn-sm {{ !$hasChart && !$definition->exportEnabled ? 'ml-auto' : '' }}"
                >
                    <i class="fas fa-sliders-h mr-1.5"></i>Customise
                </button>

            </div>

            {{-- Edit mode toolbar --}}
            <div class="flex flex-wrap items-center gap-3" x-show="editMode" x-cloak>

                {{-- Presets dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button
                        type="button"
                        @click="open = !open"
                        class="arch-btn arch-btn-outline-secondary arch-btn-sm"
                    >
                        <i class="fas fa-bookmark mr-1.5"></i>Presets
                        <i class="fas fa-chevron-down ml-1.5 text-[10px]"></i>
                    </button>
                    <div
                        x-show="open"
                        @click.outside="open = false"
                        x-transition
                        class="absolute left-0 top-full mt-1 z-30 min-w-[200px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg py-1"
                    >
                        {{-- Save current as… --}}
                        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    x-model="newPresetName"
                                    @keydown.enter.prevent="savePreset(); open = false"
                                    placeholder="Name this layout…"
                                    class="arch-input arch-input-sm flex-1 text-xs"
                                />
                                <button
                                    type="button"
                                    @click="savePreset(); open = false"
                                    :disabled="!newPresetName.trim()"
                                    class="arch-btn arch-btn-secondary arch-btn-sm"
                                >Save</button>
                            </div>
                        </div>
                        {{-- Saved presets --}}
                        <template x-if="presets.length === 0">
                            <p class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500">No saved presets</p>
                        </template>
                        <template x-for="preset in presets" :key="preset.name">
                            <div class="flex items-center justify-between px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 group">
                                <button
                                    type="button"
                                    @click="loadPreset(preset.name); open = false"
                                    class="text-sm text-gray-700 dark:text-gray-200 flex-1 text-left"
                                    x-text="preset.name"
                                ></button>
                                <button
                                    type="button"
                                    @click.stop="deletePreset(preset.name)"
                                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-opacity ml-2"
                                    title="Delete preset"
                                >
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Configure (show/hide) dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button
                        type="button"
                        @click="open = !open"
                        class="arch-btn arch-btn-outline-secondary arch-btn-sm"
                    >
                        <i class="fas fa-eye mr-1.5"></i>Configure
                        <i class="fas fa-chevron-down ml-1.5 text-[10px]"></i>
                    </button>
                    <div
                        x-show="open"
                        @click.outside="open = false"
                        x-transition
                        class="absolute right-0 top-full mt-1 z-30 min-w-[220px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg py-2"
                    >
                        <p class="px-3 pb-1 text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Sections</p>
                        <template x-for="s in sortedSections" :key="s.key">
                            <label class="flex items-center gap-3 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    :checked="s.visible"
                                    @change="toggleVisible(s.key)"
                                    :disabled="visibleKeys.length <= 1 && s.visible"
                                    class="rounded text-primary-600 focus:ring-primary-500"
                                />
                                <span
                                    class="text-sm text-gray-700 dark:text-gray-200"
                                    x-text="@js(collect($sectionsMeta)->pluck('title', 'key')->all())[s.key] ?? s.key"
                                ></span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Reset --}}
                <button
                    type="button"
                    @click="reset()"
                    class="arch-btn arch-btn-outline-secondary arch-btn-sm"
                >
                    <i class="fas fa-undo mr-1.5"></i>Reset
                </button>

                {{-- Done --}}
                <button
                    type="button"
                    @click="toggleEdit()"
                    class="arch-btn arch-btn-secondary arch-btn-sm ml-auto"
                >
                    <i class="fas fa-check mr-1.5"></i>Done
                </button>

            </div>
        </div>
    </div>

    {{-- ── Empty state (all sections hidden) ────────────────────────────── --}}
    <div
        x-show="allHidden"
        class="arch-card text-center py-12"
    >
        <i class="fas fa-eye-slash text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">You've hidden all sections.</p>
        <button
            type="button"
            @click="reset()"
            class="arch-btn arch-btn-outline-secondary arch-btn-sm"
        >
            <i class="fas fa-undo mr-1.5"></i>Reset layout
        </button>
    </div>

    {{-- ── Sections grid ─────────────────────────────────────────────────── --}}
    @if (! $hasError && $definition->sections === [])
        <x-architect::empty-state :title="__('No sections configured for this dashboard.')" />
    @endif
    <div
        x-ref="sectionsContainer"
        class="grid grid-cols-12 gap-4"
        x-show="!allHidden"
    >
        @foreach ($definition->sections as $i => $section)
            @php
                $span       = $definition->sectionSpans[$i] ?? 12;
                $sectionKey = $section->key ?? "section-{$i}";
                $data       = $resolvedSections[$i] ?? [];
                $colClass   = match ($span) {
                    1  => 'col-span-1',
                    2  => 'col-span-2',
                    3  => 'col-span-3',
                    4  => 'col-span-4',
                    5  => 'col-span-5',
                    6  => 'col-span-6',
                    7  => 'col-span-7',
                    8  => 'col-span-8',
                    9  => 'col-span-9',
                    10 => 'col-span-10',
                    11 => 'col-span-11',
                    default => 'col-span-12',
                };
            @endphp
            <div
                wire:key="{{ $sectionKey }}"
                class="{{ $colClass }} relative group/section"
                data-section-key="{{ $sectionKey }}"
                x-show="isVisible('{{ $sectionKey }}')"
                x-transition:leave="transition-opacity duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                :style="{
                    'grid-column': fullscreenKey !== '{{ $sectionKey }}' ? 'span ' + getSpan('{{ $sectionKey }}') + ' / span ' + getSpan('{{ $sectionKey }}') : undefined,
                }"
                :class="fullscreenKey === '{{ $sectionKey }}'
                    ? 'fixed inset-0 z-50 overflow-y-auto bg-gray-50 dark:bg-gray-950 p-6'
                    : ''"
            >
                {{-- Floating section controls: drag handle, width/height steppers, fullscreen --}}
                <div
                    class="absolute top-2 right-2 z-10 flex items-center rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-sm overflow-hidden opacity-0 group-hover/section:opacity-100 transition-opacity duration-150"
                    :class="editMode ? 'opacity-100' : ''"
                    x-show="fullscreenKey !== '{{ $sectionKey }}'"
                >
                    {{-- Edit-mode controls (drag + width + height) — hidden until Customise is active --}}
                    <div class="flex items-center border-r border-gray-200 dark:border-gray-600" x-show="editMode">

                        {{-- Drag handle --}}
                        <span
                            class="dash-drag-handle cursor-grab px-2 py-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border-r border-gray-200 dark:border-gray-600"
                            title="Drag to reorder"
                        >
                            <i class="fas fa-grip-vertical text-xs"></i>
                        </span>

                        {{-- Width stepper --}}
                        <div class="flex items-center border-r border-gray-200 dark:border-gray-600">
                            <button
                                type="button"
                                @click.stop="stepSpan('{{ $sectionKey }}', -1)"
                                :disabled="atSpanMin('{{ $sectionKey }}')"
                                class="px-1.5 py-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed"
                                title="Narrower"
                            ><i class="fas fa-chevron-left text-[9px]"></i></button>
                            <span class="flex flex-col items-center select-none px-1">
                                <span class="text-[8px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 leading-none">W</span>
                                <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 leading-none mt-0.5 min-w-[1.75rem] text-center" x-text="getSpanLabel('{{ $sectionKey }}')"></span>
                            </span>
                            <button
                                type="button"
                                @click.stop="stepSpan('{{ $sectionKey }}', 1)"
                                :disabled="atSpanMax('{{ $sectionKey }}')"
                                class="px-1.5 py-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed"
                                title="Wider"
                            ><i class="fas fa-chevron-right text-[9px]"></i></button>
                        </div>

                        {{-- Height stepper --}}
                        <div class="flex items-center">
                            <button
                                type="button"
                                @click.stop="stepHeight('{{ $sectionKey }}', -1)"
                                :disabled="atHeightMin('{{ $sectionKey }}')"
                                class="px-1.5 py-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed"
                                title="Shorter"
                            ><i class="fas fa-chevron-up text-[9px]"></i></button>
                            <span class="flex flex-col items-center select-none px-1">
                                <span class="text-[8px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 leading-none">H</span>
                                <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300 leading-none mt-0.5 min-w-[1.75rem] text-center" x-text="getHeightLabel('{{ $sectionKey }}')"></span>
                            </span>
                            <button
                                type="button"
                                @click.stop="stepHeight('{{ $sectionKey }}', 1)"
                                :disabled="atHeightMax('{{ $sectionKey }}')"
                                class="px-1.5 py-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed"
                                title="Taller"
                            ><i class="fas fa-chevron-down text-[9px]"></i></button>
                        </div>

                    </div>

                    {{-- Fullscreen expand (always visible on hover) --}}
                    <button
                        type="button"
                        @click.stop="setFullscreen('{{ $sectionKey }}')"
                        class="px-2 py-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                        title="Expand to full screen"
                    >
                        <i class="fas fa-expand text-xs"></i>
                    </button>
                </div>

                {{-- Close button when fullscreen --}}
                <div
                    class="flex justify-end mb-4"
                    x-show="fullscreenKey === '{{ $sectionKey }}'"
                >
                    <button
                        type="button"
                        @click="closeFullscreen()"
                        class="arch-btn arch-btn-outline-secondary arch-btn-sm"
                    >
                        <i class="fas fa-compress mr-1.5"></i>Close fullscreen
                    </button>
                </div>

                {{-- Section content --}}
                <div :style="getMinHeight('{{ $sectionKey }}') ? { minHeight: getMinHeight('{{ $sectionKey }}') } : {}">
                    @include('architect::stats.render-section', [
                        'section'     => $section,
                        'data'        => $data,
                        'granularity' => $granularity,
                    ])
                </div>

            </div>
        @endforeach
    </div>

    {{-- Poll wrapper --}}
</div>

@if ($definition->pollSeconds)
<script>
    (() => {
        // Attach wire:poll dynamically so the interval is configurable
        const root = document.currentScript.previousElementSibling;
        if (root) {
            root.setAttribute('wire:poll.{{ $definition->pollSeconds * 1000 }}ms', '');
        }
    })();
</script>
@endif
