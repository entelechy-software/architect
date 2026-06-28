<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Visual grouping of fields with no data storage of its own.
 *
 * Unlike the other classes in this namespace, Fieldset implements
 * StructureItem directly rather than extending Field — it has a label
 * and nested structure but holds no value and posts nothing.
 */
final class Fieldset implements StructureItem
{
    private string $label = '';

    /** @var array<int, StructureItem> */
    private array $structure = [];

    private int $columns = 1;

    private function __construct() {}

    public static function make(string $label = ''): static
    {
        $instance = new self;
        $instance->label = $label;

        return $instance;
    }

    /** @param  array<int, StructureItem>  $items */
    public function structure(array $items): static
    {
        $clone = clone $this;
        $clone->structure = $items;

        return $clone;
    }

    public function columns(int $columns): static
    {
        $clone = clone $this;
        $clone->columns = $columns;

        return $clone;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /** @return array<int, StructureItem> */
    public function getStructure(): array
    {
        return $this->structure;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }
}
