<?php

declare(strict_types=1);

namespace Entelechy\Architect\Stats\Contracts;

use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;
use Entelechy\Architect\Stats\ArchitectStatDefinition;

/**
 * Implemented by a host-app class exposing the static definition() method
 * that Stats\Livewire\DashboardEngine drives.
 */
interface ProvidesStatDefinition extends ArchitectDefinitionProvider
{
    public static function definition(): ArchitectStatDefinition;
}
