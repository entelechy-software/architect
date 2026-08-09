<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Contracts;

use Entelechy\Architect\Content\ArchitectContentDefinition;
use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;

/**
 * Implemented by a host-app class exposing the static definition() method
 * that Content\Livewire\ContentEngine drives.
 */
interface ProvidesContentDefinition extends ArchitectDefinitionProvider
{
    public static function definition(): ArchitectContentDefinition;
}
