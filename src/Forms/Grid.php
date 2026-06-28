<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Lays out a set of fields in a responsive column grid.
 *
 * Renders as <div class="arch-grid" data-cols="{n}"> — reuses the same
 * layout primitive already shipped for dashboards in Phase 2/3.
 */
final class Grid implements StructureItem
{
    private int $cols;

    private string $gap = 'md';

    /** @var array<int, StructureItem> */
    private array $structure = [];

    private function __construct(int $cols)
    {
        $this->cols = $cols;
    }

    public static function make(int $cols = 2): static
    {
        return new self($cols);
    }

    public function gap(string $gap): static
    {
        $clone = clone $this;
        $clone->gap = $gap;

        return $clone;
    }

    /** @param  array<int, StructureItem>  $items */
    public function structure(array $items): static
    {
        $clone = clone $this;
        $clone->structure = $items;

        return $clone;
    }

    public function getCols(): int
    {
        return $this->cols;
    }

    public function getGap(): string
    {
        return $this->gap;
    }

    /** @return array<int, StructureItem> */
    public function getStructure(): array
    {
        return $this->structure;
    }
}
