<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch;

use Entelechy\Architect\Supersearch\SearchSets\ModelSearchSet;
use Entelechy\Architect\Supersearch\SearchSets\NavigationSearchSet;

/**
 * Immutable DTO produced by SupersearchBuilder::build().
 * Stored on the Livewire engine as $definitionClass and reconstructed per request.
 */
final class ArchitectSupersearchDefinition
{
    /**
     * @param  list<ModelSearchSet|NavigationSearchSet>  $searchSets
     */
    public function __construct(
        public readonly string $key,
        public readonly string $placeholder,
        public readonly string $shortcut,
        public readonly array $searchSets,
        public readonly ?string $permission = null,
    ) {}
}
