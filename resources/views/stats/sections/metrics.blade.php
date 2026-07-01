{{--
    Metrics section — grid or stacked arrangement of MetricCard KPI cards.

    Variables:
        $section     ArchitectStatDefinition (type = 'metrics')
        $data        array — [{card: MetricCard, value: mixed, live: bool}, ...]
        $sectionKey  string|null — parent dashboard section key (forwarded from engine).
                     When present, enables drag-to-reorder cards via dashboardEdit Alpine state.
--}}
@php
    $wrapCard   = $section->card ?? true;
    $layout     = $section->layout ?? 'inline';
    $cols       = $section->columns ?? 4;
    $sectionKey = $sectionKey ?? null;
    $totalCards = count($data);

    $gridClass = $layout === 'stacked'
        ? 'flex flex-col gap-3'
        : match ($cols) {
            1 => 'grid grid-cols-1 gap-3',
            2 => 'grid grid-cols-1 sm:grid-cols-2 gap-3',
            3 => 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3',
            5 => 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3',
            6 => 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3',
            default => 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3',
        };
@endphp

@if ($wrapCard && $section->title)
    <div class="arch-card">
        <div class="arch-card-header">
            <h6 class="arch-card-title">{{ $section->title }}</h6>
        </div>
        <div class="arch-card-body">
            <div class="{{ $gridClass }}">
                @foreach ($data as $cardIdx => $item)
                    @if ($sectionKey)
                        <div
                            class="relative h-full arch-card-drag-wrapper"
                            :draggable="editMode ? 'true' : 'false'"
                            :style="{ order: getCardVisualOrder('{{ $sectionKey }}', {{ $cardIdx }}) }"
                            @dragstart.stop="cardDragStart('{{ $sectionKey }}', {{ $cardIdx }}, $event)"
                            @dragend.stop="cardDragEnd()"
                            @dragover.prevent.stop="cardDragOver('{{ $sectionKey }}', {{ $cardIdx }}, $event)"
                            @dragleave.stop="cardDragLeave('{{ $sectionKey }}', {{ $cardIdx }})"
                            @drop.prevent.stop="cardDrop('{{ $sectionKey }}', {{ $cardIdx }}, {{ $totalCards }})"
                            :class="{
                                'arch-card--dragging': cardDragSectionKey === '{{ $sectionKey }}' && cardDragIdx === {{ $cardIdx }},
                                'arch-card--drag-over': cardDragSectionKey === '{{ $sectionKey }}' && cardDragOverIdx === {{ $cardIdx }},
                            }"
                        >
                            @include('architect::stats.partials.metric-card', ['item' => $item])
                            <div class="arch-card-drag-handle" @mousedown.stop="handleCardMouseDown()" title="Drag to reorder">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                        </div>
                    @else
                        @include('architect::stats.partials.metric-card', ['item' => $item])
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="{{ $gridClass }}">
        @foreach ($data as $cardIdx => $item)
            @if ($sectionKey)
                <div
                    class="relative h-full arch-card-drag-wrapper"
                    :draggable="editMode ? 'true' : 'false'"
                    :style="{ order: getCardVisualOrder('{{ $sectionKey }}', {{ $cardIdx }}) }"
                    @dragstart.stop="cardDragStart('{{ $sectionKey }}', {{ $cardIdx }}, $event)"
                    @dragend.stop="cardDragEnd()"
                    @dragover.prevent.stop="cardDragOver('{{ $sectionKey }}', {{ $cardIdx }}, $event)"
                    @dragleave.stop="cardDragLeave('{{ $sectionKey }}', {{ $cardIdx }})"
                    @drop.prevent.stop="cardDrop('{{ $sectionKey }}', {{ $cardIdx }}, {{ $totalCards }})"
                    :class="{
                        'arch-card--dragging': cardDragSectionKey === '{{ $sectionKey }}' && cardDragIdx === {{ $cardIdx }},
                        'arch-card--drag-over': cardDragSectionKey === '{{ $sectionKey }}' && cardDragOverIdx === {{ $cardIdx }},
                    }"
                >
                    @include('architect::stats.partials.metric-card', ['item' => $item])
                    <div class="arch-card-drag-handle" @mousedown.stop="handleCardMouseDown()" title="Drag to reorder">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                </div>
            @else
                @include('architect::stats.partials.metric-card', ['item' => $item])
            @endif
        @endforeach
    </div>
@endif
