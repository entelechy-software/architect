<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Contracts;

use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;
use Entelechy\Architect\Supersearch\ArchitectSupersearchDefinition;

/**
 * Implemented by a host-app class exposing the static definition() method
 * that Supersearch\Livewire\SupersearchEngine drives.
 */
interface ProvidesSupersearchDefinition extends ArchitectDefinitionProvider
{
    public static function definition(): ArchitectSupersearchDefinition;
}
