<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * A single block type available inside a Builder field
 * (e.g. Block::make('image')->structure([...])).
 */
final class Block
{
    private string $label = '';

    /** @var array<int, StructureItem> */
    private array $structure = [];

    private function __construct(private readonly string $name) {}

    public static function make(string $name): static
    {
        return new self($name);
    }

    public function label(string $label): static
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    /** @param  array<int, StructureItem>  $items */
    public function structure(array $items): static
    {
        $clone = clone $this;
        $clone->structure = $items;

        return $clone;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label !== '' ? $this->label : str($this->name)->headline()->toString();
    }

    /** @return array<int, StructureItem> */
    public function getStructure(): array
    {
        return $this->structure;
    }
}
