{{--
    Chart section — ApexCharts time-series or categorical chart.

    Variables:
        $section      ArchitectStatDefinition (type = 'chart')
        $data         array{series: array, granularity: string}
        $granularity  string — H | D | M | A
--}}
@php
    $chartConfig = [
        'type'        => $section->style ?? 'line',
        'title'       => $section->title,
        'series'      => $data['series'] ?? [],
        // Categorical bar keys (null when time-series)
        'categories'  => $data['categories'] ?? null,
        'horizontal'  => $data['horizontal'] ?? false,
        'stacked'     => $data['stacked'] ?? false,
        // Donut keys (null when not donut)
        'labels'      => $data['labels'] ?? null,
        // Time-series granularity (ignored by categorical/donut)
        'granularity' => $data['granularity'] ?? $granularity,
    ];
@endphp

<div class="arch-card">
    @if ($section->title)
        <div class="arch-card-header">
            <h6 class="arch-card-title">{{ $section->title }}</h6>
        </div>
    @endif
    <div class="arch-card-body">
        <div
            x-data="architectChart({{ json_encode($chartConfig) }})"
            x-init="init()"
            x-destroy="destroy()"
            style="min-height: 300px;"
        ></div>
    </div>
</div>
