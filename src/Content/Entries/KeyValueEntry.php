<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Entries;

/**
 * Renders an associative array value as a static key/value table.
 *
 * Usage:
 *   KeyValueEntry::make('metadata')->keyLabel('Field')->valueLabel('Value')
 */
class KeyValueEntry extends Entry
{
    protected string $keyLabel = 'Key';

    protected string $valueLabel = 'Value';

    public function keyLabel(string $label): static
    {
        $clone = clone $this;
        $clone->keyLabel = $label;

        return $clone;
    }

    public function valueLabel(string $label): static
    {
        $clone = clone $this;
        $clone->valueLabel = $label;

        return $clone;
    }

    public function getKeyLabel(): string
    {
        return $this->keyLabel;
    }

    public function getValueLabel(): string
    {
        return $this->valueLabel;
    }

    public function getViewName(): string
    {
        return 'architect::content.entries.key-value';
    }
}
