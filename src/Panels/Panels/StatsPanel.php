<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels\Panels;

use Entelechy\Architect\Panels\ArchitectPanelDefinition;
use Entelechy\Architect\Panels\Contracts\Panel;
use Entelechy\Architect\Stats\Elements\MetricCard;

/**
 * Panel that renders a grid of MetricCard KPI statistics.
 *
 * Usage:
 *   StatsPanel::make()
 *       ->title('Membership Overview')
 *       ->columns(3)
 *       ->cards([
 *           MetricCard::make('total_members')->label('Total Members')->value(fn () => Member::count()),
 *       ])
 */
class StatsPanel implements Panel
{
    protected ?string $title = null;

    protected int $columns = 3;

    /** @var array<int, MetricCard> */
    protected array $cards = [];

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

    public function columns(int $columns): static
    {
        $clone = clone $this;
        $clone->columns = $columns;

        return $clone;
    }

    /** @param array<int, MetricCard> $cards */
    public function cards(array $cards): static
    {
        $clone = clone $this;
        $clone->cards = $cards;

        return $clone;
    }

    public function getType(): string
    {
        return 'stats';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    /** @return array<int, MetricCard> */
    public function getCards(): array
    {
        return $this->cards;
    }

    public function build(): ArchitectPanelDefinition
    {
        return new ArchitectPanelDefinition(
            type: $this->getType(),
            title: $this->title,
            config: [
                'columns' => $this->columns,
                'cards' => $this->cards,
            ],
            panel: $this,
        );
    }
}
