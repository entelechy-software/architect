<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;
use Entelechy\Architect\Table\ArchitectTableDefinition;

/**
 * Implemented by a host-app class exposing the static definition() method
 * that Table\Livewire\Engine (and friends: FormPanel, ImportWizard) drive.
 */
interface ProvidesTableDefinition extends ArchitectDefinitionProvider
{
    public static function definition(): ArchitectTableDefinition;
}
