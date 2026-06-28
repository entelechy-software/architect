<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels;

use Entelechy\Architect\Panels\Contracts\Panel;

/**
 * Fluent builder for a dashboard composed of multiple panels.
 *
 * Usage:
 *   Architect::dashboard('home')
 *       ->panel(StatsPanel::make()->cards([...]), span: 12)
 *       ->panel(ChartPanel::make()->style('area'), span: 8)
 *       ->panel(EmbeddedTablePanel::make()->definition(RecentOrdersTable::class), span: 4)
 *       ->build();
 */
final class DashboardBuilder
{
    /** @var array<int, array{panel: Panel, span: int}> */
    private array $panels = [];

    private function __construct(private string $key) {}

    public static function make(string $key): static
    {
        return new self($key);
    }

    public function panel(Panel $panel, int $span = 12): static
    {
        $this->panels[] = ['panel' => $panel, 'span' => $span];

        return $this;
    }

    public function build(): ArchitectDashboardDefinition
    {
        return new ArchitectDashboardDefinition(
            key: $this->key,
            panels: $this->panels,
        );
    }
}
