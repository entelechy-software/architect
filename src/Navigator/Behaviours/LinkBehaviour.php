<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Behaviours;

/**
 * Navigate to a URL when the item is activated.
 *
 * Renders as a plain anchor <a href="$url"> in Phase A.
 * Phase B will extend to support Turbo/SPA transitions.
 */
final class LinkBehaviour
{
    public function __construct(
        public readonly string $url,
    ) {}

    public function behaviourType(): string
    {
        return 'link';
    }
}
