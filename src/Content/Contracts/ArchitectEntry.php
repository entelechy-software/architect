<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Contracts;

/**
 * Contract for every Content entry (Entelechy\Architect\Content\Entries\*).
 *
 * An entry is a read-only display counterpart to a Forms field: it
 * resolves a value off a record and renders it via a Blade view, but
 * never accepts input or carries validation rules.
 */
interface ArchitectEntry
{
    public function getName(): string;

    public function getLabel(): string;

    /** The Blade view (architect:: namespaced) that renders this entry. */
    public function getViewName(): string;

    public function resolveValue(mixed $record): mixed;
}
