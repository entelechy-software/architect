<?php

declare(strict_types=1);

namespace Entelechy\Architect\Stats;

use Carbon\CarbonImmutable;

/**
 * Immutable date range value passed into every section callable by the
 * DashboardEngine. Sections that need the current filter range
 * type-hint this in their callable signature.
 *
 * Sections that don't depend on a date range (e.g. "open cases right now")
 * omit it from their callable signature — the engine detects this via
 * ReflectionFunction and injects only Container.
 */
final class DateRange
{
    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    /**
     * Return both timestamps as Unix integers, convenient for raw SQL bindings.
     *
     * @return array{from: int, to: int}
     */
    public function toUnix(): array
    {
        return [
            'from' => $this->from->getTimestamp(),
            'to' => $this->to->getTimestamp(),
        ];
    }

    /**
     * Human-readable label for display in the date filter UI.
     */
    public function getLabel(): string
    {
        return $this->from->format('d/m/Y').' – '.$this->to->format('d/m/Y');
    }
}
