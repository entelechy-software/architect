<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content;

use Entelechy\Architect\Content\Contracts\ArchitectEntry;

/**
 * Frozen value object produced by ContentBuilder::build().
 *
 * Consumed by Content\Livewire\ContentEngine — never constructed directly
 * by host-app code.
 */
final class ArchitectContentDefinition
{
    /**
     * @param  array<int, ArchitectEntry>  $structure
     */
    public function __construct(
        public readonly mixed $record,
        public readonly array $structure,
        public readonly int $columns,
    ) {}
}
