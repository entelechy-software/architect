<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content;

use Entelechy\Architect\Content\Contracts\ArchitectEntry;

/**
 * Fluent builder for a standalone Architect content (read-only display) panel.
 *
 * Usage:
 *   Architect::content()
 *       ->record($election)
 *       ->structure([
 *           TextEntry::make('name'),
 *           IconEntry::make('status')->icon(fn ($v) => $v === 'open' ? 'fas fa-lock-open' : 'fas fa-lock'),
 *       ])
 *       ->columns(2)
 *       ->build();
 */
final class ContentBuilder
{
    /** @var array<int, ArchitectEntry> */
    private array $structure = [];

    private mixed $record = null;

    private int $columns = 1;

    public static function make(): static
    {
        return new self;
    }

    /** Eloquent model or array the entries resolve their values from. */
    public function record(mixed $data): static
    {
        $this->record = $data;

        return $this;
    }

    /** @param  array<int, ArchitectEntry>  $entries */
    public function structure(array $entries): static
    {
        $this->structure = $entries;

        return $this;
    }

    /** Fluent shorthand: ->entry(TextEntry::make('name')) */
    public function entry(ArchitectEntry $entry): static
    {
        $this->structure[] = $entry;

        return $this;
    }

    public function columns(int $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function build(): ArchitectContentDefinition
    {
        return new ArchitectContentDefinition(
            record: $this->record,
            structure: $this->structure,
            columns: $this->columns,
        );
    }
}
