<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Contracts;

use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;
use Entelechy\Architect\Navigator\ArchitectNavigatorDefinition;

/**
 * Implemented by a host-app class exposing the static definition() method
 * that Navigator\Livewire\SpaTabsEngine, Navigator\Livewire\ModuleTabsManager,
 * and the <x-architect::static> Blade renderer all drive.
 */
interface ProvidesNavigatorDefinition extends ArchitectDefinitionProvider
{
    public static function definition(): ArchitectNavigatorDefinition;
}
