<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Block-based content builder (Gutenberg-like) — an ordered list of
 * heterogeneous blocks, each its own structure of fields.
 */
class Builder extends Field
{
    /** @var array<int, Block> */
    private array $blocks = [];

    /** @param  array<int, Block>  $blocks */
    public function blocks(array $blocks): static
    {
        $clone = clone $this;
        $clone->blocks = $blocks;

        return $clone;
    }

    /** @return array<int, Block> */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.builder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
