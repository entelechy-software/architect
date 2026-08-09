<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Contracts;

use Entelechy\Architect\Contracts\ArchitectDefinitionProvider;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;

/**
 * Implemented by a host-app class exposing the static definition() method
 * that Forms\Livewire\WizardEngine drives.
 */
interface ProvidesWizardDefinition extends ArchitectDefinitionProvider
{
    public static function definition(): ArchitectWizardDefinition;
}
