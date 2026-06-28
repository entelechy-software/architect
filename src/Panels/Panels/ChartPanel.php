<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels\Panels;

use Closure;
use Entelechy\Architect\Panels\ArchitectPanelDefinition;
use Entelechy\Architect\Panels\Contracts\Panel;

/**
 * Panel that renders an ApexCharts-powered chart.
 *
 * Usage:
 *   ChartPanel::make()
 *       ->title('New Members')
 *       ->style('area')
 *       ->series(fn (DateRange $range) => [...])
 *       ->requiresDateRange()
 */
class ChartPanel implements Panel
{
    protected ?string $title = null;

    protected string $style = 'line';

    protected ?Closure $seriesCallable = null;

    protected bool $dateRangeRequired = false;

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    public function title(string $title): static
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    /** @param 'line'|'area'|'bar'|'donut'|'pie' $style */
    public function style(string $style): static
    {
        $clone = clone $this;
        $clone->style = $style;

        return $clone;
    }

    public function series(Closure $callable): static
    {
        $clone = clone $this;
        $clone->seriesCallable = $callable;

        return $clone;
    }

    public function requiresDateRange(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->dateRangeRequired = $condition;

        return $clone;
    }

    public function getType(): string
    {
        return 'chart';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getSeriesCallable(): ?Closure
    {
        return $this->seriesCallable;
    }

    public function isDateRangeRequired(): bool
    {
        return $this->dateRangeRequired;
    }

    public function build(): ArchitectPanelDefinition
    {
        return new ArchitectPanelDefinition(
            type: $this->getType(),
            title: $this->title,
            config: [
                'style' => $this->style,
                'requiresDateRange' => $this->dateRangeRequired,
            ],
            panel: $this,
        );
    }
}
