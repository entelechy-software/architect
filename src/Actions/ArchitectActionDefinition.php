<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions;

use Entelechy\Architect\Actions\Contracts\ArchitectAction;

/**
 * Immutable value object representing a resolved list of named actions.
 *
 * Produced by ActionBuilder::build(). Consumed by host-app code that needs
 * to enumerate available action class names — for example to render an
 * action toolbar above a table.
 */
final class ArchitectActionDefinition
{
    /**
     * @param  string  $key  Stable identifier for this action set.
     * @param  array<int, class-string<ArchitectAction>>  $actionClasses  Ordered list of action FQCNs.
     */
    public function __construct(
        public readonly string $key,
        public readonly array $actionClasses,
    ) {}
}
