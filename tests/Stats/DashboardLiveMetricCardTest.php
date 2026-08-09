<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Stats;

use Entelechy\Architect\Stats\ArchitectStatDefinition;
use Entelechy\Architect\Stats\Contracts\ProvidesStatDefinition;
use Entelechy\Architect\Stats\Elements\MetricCard;
use Entelechy\Architect\Stats\Livewire\DashboardEngine;
use Entelechy\Architect\Tests\TestCase;

/**
 * Regression coverage for a Phase 2 wiring-audit finding: MetricCard::live()
 * cards were never resolved by DashboardEngine::resolveMetrics() — the branch
 * unconditionally returned ['value' => null, ...], so live cards rendered
 * "—" forever regardless of the dashboard's ->poll() interval.
 */
class DashboardLiveMetricCardTest extends TestCase
{
    public function test_live_metric_card_value_is_resolved_on_render(): void
    {
        $engine = app(DashboardEngine::class);
        $engine->definitionClass = LiveMetricDashboardDefinition::class;

        $view = $engine->render();

        $resolvedSections = $view->getData()['resolvedSections'];
        $cardData = $resolvedSections[0][0];

        $this->assertTrue($cardData['live']);
        $this->assertSame(42, $cardData['value']);
    }
}

final class LiveMetricDashboardDefinition implements ProvidesStatDefinition
{
    public static function definition(): ArchitectStatDefinition
    {
        $metricsSection = new ArchitectStatDefinition(
            type: 'metrics',
            style: null,
            title: 'Open Cases',
            key: 'open-cases',
            pageTitle: null,
            breadcrumbs: [],
            card: true,
            requiresDateRange: false,
            defaultGranularity: 'D',
            pollSeconds: null,
            exportEnabled: false,
            sections: [],
            sectionSpans: [],
            columns: 4,
            layout: 'inline',
            cards: [
                MetricCard::make('Open Cases')->live(fn () => 42, every: 30),
            ],
            seriesCallable: null,
            dataCallable: null,
            dataRequiresDateRange: false,
            scrollableHeight: null,
            permission: null,
        );

        return new ArchitectStatDefinition(
            type: 'dashboard',
            style: null,
            title: null,
            key: 'live-metric-dashboard',
            pageTitle: 'Live Metric Dashboard',
            breadcrumbs: [],
            card: true,
            requiresDateRange: false,
            defaultGranularity: 'D',
            pollSeconds: 30,
            exportEnabled: false,
            sections: [$metricsSection],
            sectionSpans: [12],
            columns: 4,
            layout: 'inline',
            cards: [],
            seriesCallable: null,
            dataCallable: null,
            dataRequiresDateRange: false,
            scrollableHeight: null,
            permission: null,
        );
    }
}
