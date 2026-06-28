{{--
    Metrics section — grid or stacked arrangement of MetricCard KPI cards.

    Variables:
        $section  ArchitectStatDefinition (type = 'metrics')
        $data     array — [{card: MetricCard, value: mixed, live: bool}, ...]
                  OR a Closure-resolved array of MetricCard if ->cards(fn...) was used
--}}
@php
    $wrapCard = $section->card ?? true;
    $layout   = $section->layout ?? 'inline';
    $cols     = $section->columns ?? 4;

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
                @foreach ($data as $item)
                    @include('architect::stats.partials.metric-card', ['item' => $item])
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="{{ $gridClass }}">
        @foreach ($data as $item)
            @include('architect::stats.partials.metric-card', ['item' => $item])
        @endforeach
    </div>
@endif
