@php
/**
 * @var \Entelechy\Architect\Panels\Panels\ChartPanel $panel
 * @var \Entelechy\Architect\Panels\ArchitectPanelDefinition $def
 * @var int $panelIndex  Stable index passed by panels/engine.blade.php for deterministic IDs.
 */
$chartId = 'arch-chart-' . $panelIndex;
$seriesCallable = $panel->getSeriesCallable();
// Date-range charts cannot resolve without a range context — no DateRange
// concept exists in static Panels dashboards (see stats.blade.php for the
// same convention with metric cards).
$series = ($seriesCallable !== null && ! $panel->isDateRangeRequired()) ? $seriesCallable() : [];
@endphp

<div class="arch-panel arch-panel--chart">
    @if ($def->title)
        <h3 class="arch-panel__title">{{ $def->title }}</h3>
    @endif
    <div
        class="arch-chart"
        id="{{ $chartId }}"
        data-style="{{ $panel->getStyle() }}"
        data-series="{{ json_encode($series) }}"
    ></div>
</div>
