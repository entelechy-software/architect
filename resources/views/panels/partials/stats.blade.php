@php
/**
 * @var \Entelechy\Architect\Panels\Panels\StatsPanel $panel
 * @var \Entelechy\Architect\Panels\ArchitectPanelDefinition $def
 */
@endphp

<div class="arch-panel arch-panel--stats">
    @if ($def->title)
        <h3 class="arch-panel__title">{{ $def->title }}</h3>
    @endif
    <div class="arch-stats-grid" data-cols="{{ $panel->getColumns() }}">
        @foreach ($panel->getCards() as $card)
            @php
                // Resolve value following DashboardEngine convention (no DateRange in static panels).
                $callable = $card->getValueCallable();
                $cardValue = null;
                if ($callable !== null) {
                    $cardValue = $card->isDateRangeRequired()
                        ? '—' // date-range cards cannot resolve without a range context
                        : $callable(app());
                }
            @endphp
            <div class="arch-metric-card">
                @if ($card->getIcon())
                    <span class="arch-metric-card__icon"><x-architect::icon :name="$card->getIcon()" /></span>
                @endif
                <div class="arch-metric-card__body">
                    <span class="arch-metric-card__label">{{ $card->getLabel() }}</span>
                    <span class="arch-metric-card__value">{{ $cardValue ?? '—' }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
