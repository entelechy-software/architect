<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Integer number field. Optionally constrains the accepted range via
 * ->min() and ->max().
 */
class IntegerField extends Field
{
    private ?int $min = null;

    private ?int $max = null;

    public function min(int $min): static
    {
        $clone = clone $this;
        $clone->min = $min;

        return $clone;
    }

    public function max(int $max): static
    {
        $clone = clone $this;
        $clone->max = $max;

        return $clone;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.integer';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'integer';

        if ($this->min !== null) {
            $rules[] = "min:{$this->min}";
        }

        if ($this->max !== null) {
            $rules[] = "max:{$this->max}";
        }

        return $rules;
    }
}
