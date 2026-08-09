<?php

declare(strict_types=1);

namespace Entelechy\Architect\Contracts;

/**
 * Marker interface for a host-app class that supplies a subsystem's frozen
 * definition DTO via a static definition() method.
 *
 * Every subsystem has its own narrower interface (e.g.
 * Entelechy\Architect\Table\Contracts\ProvidesTableDefinition) that extends
 * this one and declares the concrete return type — engines check against
 * those, not this one directly, except where a definition class may
 * legitimately satisfy more than one subsystem (e.g. Table\CustomForm
 * accepts either a plain form or a wizard definition class).
 */
interface ArchitectDefinitionProvider
{
    public static function definition(): object;
}
