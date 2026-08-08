<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support;

/**
 * A shared honesty label for any registered "thing" in Architect (Forms
 * controls today; conceivably Table/Navigator/Toolbar feature flags or docs
 * front-matter later) that describes how much you should trust it works.
 *
 * This is Phase 0 of the improvement plan: rather than documentation
 * silently claiming every registered field/feature is production-ready,
 * each entry now carries an explicit, queryable maturity so consumers
 * (docs generators, `architect:doctor`, host applications) can tell real
 * capability apart from aspiration.
 */
enum Maturity: string
{
    /** Implemented end-to-end and covered by tests. Safe to build on. */
    case Stable = 'stable';

    /**
     * Works for the common path, but has a known, narrower-than-advertised
     * implementation, a missing enhancement, or rough edges. Usable, but
     * read the caveat before relying on it for an edge case.
     */
    case Beta = 'beta';

    /**
     * Registered but not functionally wired up (e.g. a Blade view
     * references an Alpine component that doesn't exist yet). Will not
     * work correctly if used today.
     */
    case Experimental = 'experimental';

    /** Documented/reserved for a future release; no implementation exists yet. */
    case Planned = 'planned';

    public function label(): string
    {
        return match ($this) {
            self::Stable => 'Stable',
            self::Beta => 'Beta',
            self::Experimental => 'Experimental',
            self::Planned => 'Planned',
        };
    }

    /** Whether host applications should be warned before relying on this. */
    public function isProductionReady(): bool
    {
        return $this === self::Stable;
    }
}
