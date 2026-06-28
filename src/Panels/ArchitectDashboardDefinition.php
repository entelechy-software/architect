<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels;

use Entelechy\Architect\Panels\Contracts\Panel;

/**
 * Immutable value object for a full Architect dashboard.
 *
 * Produced by DashboardBuilder::build(). Consumed by PanelEngine.
 */
final class ArchitectDashboardDefinition
{
    /**
     * @param  string  $key  Stable identifier (used for personalisation state).
     * @param  array<int, array{panel: Panel, span: int}>  $panels  Ordered panels with column span.
     */
    public function __construct(
        public readonly string $key,
        public readonly array $panels,
    ) {}
}
