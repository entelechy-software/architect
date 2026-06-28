<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Repeatable group of fields — add/remove/reorder rows of structured data.
 */
class Repeater extends Field
{
    /** @var array<int, StructureItem> */
    private array $structure = [];

    private ?int $minItems = null;

    private ?int $maxItems = null;

    private string $addButtonLabel = 'Add item';

    /** @param  array<int, StructureItem>  $items */
    public function structure(array $items): static
    {
        $clone = clone $this;
        $clone->structure = $items;

        return $clone;
    }

    public function minItems(int $min): static
    {
        $clone = clone $this;
        $clone->minItems = $min;

        return $clone;
    }

    public function maxItems(int $max): static
    {
        $clone = clone $this;
        $clone->maxItems = $max;

        return $clone;
    }

    public function addButtonLabel(string $label): static
    {
        $clone = clone $this;
        $clone->addButtonLabel = $label;

        return $clone;
    }

    /** @return array<int, StructureItem> */
    public function getStructure(): array
    {
        return $this->structure;
    }

    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function getAddButtonLabel(): string
    {
        return $this->addButtonLabel;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.repeater';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        if ($this->minItems !== null) {
            $rules[] = "min:{$this->minItems}";
        }

        if ($this->maxItems !== null) {
            $rules[] = "max:{$this->maxItems}";
        }

        return $rules;
    }
}
