<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Contracts;

/**
 * Contract for every standalone Forms field (Entelechy\Architect\Forms\Fields\*).
 *
 * Distinct from Entelechy\Architect\Table\Contracts\ArchitectField, which is
 * the long-standing contract for fields rendered inside the Table form panel
 * and carries Table-only concerns (onCreate/onEdit, visibleTo, blade()).
 * The two hierarchies are intentionally not unified — see Forms/Fields/Field.php.
 */
interface ArchitectField extends StructureItem
{
    public function getName(): string;

    public function getLabel(): string;

    public function isRequired(): bool;

    public function getPlaceholder(): ?string;

    public function getHint(): ?string;

    public function getDefault(): mixed;

    /** @return array<int, string> */
    public function getRules(): array;

    public function getType(): string;

    /** The Blade view (architect:: namespaced) that renders this field. */
    public function getViewName(): string;
}
