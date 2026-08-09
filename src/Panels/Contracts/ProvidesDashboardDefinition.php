<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels\Contracts;

use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;
use Entelechy\Architect\Panels\ArchitectDashboardDefinition;

/**
 * Implemented by a host-app class exposing the static definition() method
 * that Panels\Livewire\PanelEngine drives.
 */
interface ProvidesDashboardDefinition extends ArchitectDefinitionProvider
{
    public static function definition(): ArchitectDashboardDefinition;
}
