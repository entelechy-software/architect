<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Exclusive (or multiple) selection rendered as a styled button group.
 */
class ToggleButtons extends Field
{
    use HasOptions;

    private bool $multiple = false;

    public function multiple(bool $multiple = true): static
    {
        $clone = clone $this;
        $clone->multiple = $multiple;

        return $clone;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.toggle-buttons';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = $this->multiple ? 'array' : 'string';

        return $rules;
    }
}
