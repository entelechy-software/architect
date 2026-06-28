<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Repeatable key/value pairs (e.g. metadata, custom attributes).
 */
class KeyValue extends Field
{
    private string $keyLabel = 'Key';

    private string $valueLabel = 'Value';

    private string $addButtonLabel = 'Add row';

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

    public function addButtonLabel(string $label): static
    {
        $clone = clone $this;
        $clone->addButtonLabel = $label;

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

    public function getAddButtonLabel(): string
    {
        return $this->addButtonLabel;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.key-value';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
