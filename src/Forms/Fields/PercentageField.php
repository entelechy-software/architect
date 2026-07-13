<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Percentage input — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value is a plain number (e.g. 15.5 for 15.5%), not a 0..1 fraction.
 */
class PercentageField extends Field
{
    private float $min = 0.0;

    private float $max = 100.0;

    private int $decimals = 0;

    public function min(float $min): static
    {
        $clone = clone $this;
        $clone->min = $min;

        return $clone;
    }

    public function max(float $max): static
    {
        $clone = clone $this;
        $clone->max = $max;

        return $clone;
    }

    public function decimals(int $decimals): static
    {
        $clone = clone $this;
        $clone->decimals = $decimals;

        return $clone;
    }

    public function getMin(): float
    {
        return $this->min;
    }

    public function getMax(): float
    {
        return $this->max;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.percentage';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'numeric';
        $rules[] = "min:{$this->min}";
        $rules[] = "max:{$this->max}";

        return $rules;
    }
}
