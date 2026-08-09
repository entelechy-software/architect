<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Contracts;

use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;
use Entelechy\Architect\Toolbar\ArchitectToolbarDefinition;

/**
 * Implemented by a host-app class exposing the static definition() method
 * that Toolbar\Livewire\ToolbarEngine drives.
 */
interface ProvidesToolbarDefinition extends ArchitectDefinitionProvider
{
    public static function definition(): ArchitectToolbarDefinition;
}
