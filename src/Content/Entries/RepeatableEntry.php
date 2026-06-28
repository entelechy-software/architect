<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Entries;

use Entelechy\Architect\Content\Contracts\ArchitectEntry;
use Illuminate\Support\Collection;

/**
 * Resolves a relation/array on the record and renders a sub-structure of
 * entries once per item — e.g. a list of line items or attachments.
 *
 * Usage:
 *   RepeatableEntry::make('lineItems')
 *       ->structure([TextEntry::make('description'), TextEntry::make('amount')])
 *       ->columns(2)
 */
class RepeatableEntry extends Entry
{
    /** @var list<ArchitectEntry> */
    protected array $structure = [];

    protected int $columns = 1;

    /** @param  list<ArchitectEntry>  $entries */
    public function structure(array $entries): static
    {
        $clone = clone $this;
        $clone->structure = $entries;

        return $clone;
    }

    public function columns(int $columns): static
    {
        $clone = clone $this;
        $clone->columns = $columns;

        return $clone;
    }

    /** @return list<ArchitectEntry> */
    public function getStructure(): array
    {
        return $this->structure;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    /** @return array<int, mixed> */
    public function resolveItems(mixed $record): array
    {
        $value = parent::resolveValue($record);

        if ($value instanceof Collection) {
            return $value->values()->all();
        }

        if (is_array($value)) {
            return array_values($value);
        }

        return [];
    }

    public function getViewName(): string
    {
        return 'architect::content.entries.repeatable';
    }
}
