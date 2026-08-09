<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Stats;

use Entelechy\Architect\Navigator\Livewire\SpaSharedDefinition;
use Entelechy\Architect\Stats\ArchitectStatDefinition;
use Entelechy\Architect\Stats\Livewire\DashboardEngine;
use Entelechy\Architect\Tests\TestCase;
use Entelechy\Architect\Stats\Contracts\ProvidesStatDefinition;

class DashboardBreadcrumbsTest extends TestCase
{
    public function test_dashboard_engine_shares_normalized_breadcrumbs_with_layout(): void
    {
        view()->share('definition', null);

        $engine = app(DashboardEngine::class);
        $engine->definitionClass = DashboardBreadcrumbDefinition::class;
        $engine->render();

        $shared = view()->shared('definition');

        $this->assertInstanceOf(SpaSharedDefinition::class, $shared);
        $this->assertSame('Reports', $shared->breadcrumbs[0]['title']);
        $this->assertSame('/admin/reports/monthly', $shared->breadcrumbs[0]['menu'][0]['url']);
    }
}

final class DashboardBreadcrumbDefinition implements ProvidesStatDefinition
{
    public static function definition(): ArchitectStatDefinition
    {
        return new ArchitectStatDefinition(
            type: 'dashboard',
            style: null,
            title: 'Reports',
            key: 'reports',
            pageTitle: 'Reports',
            breadcrumbs: [
                [
                    'title' => 'Reports',
                    'url' => '/admin/reports',
                    'menu' => [
                        ['title' => 'Monthly', 'url' => '/admin/reports/monthly'],
                    ],
                ],
            ],
            card: false,
            requiresDateRange: false,
            defaultGranularity: 'D',
            pollSeconds: null,
            exportEnabled: false,
            sections: [],
            sectionSpans: [],
            columns: 1,
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
