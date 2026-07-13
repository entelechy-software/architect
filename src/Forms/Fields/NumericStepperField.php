<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Numeric input with explicit increment/decrement controls and a
 * configurable step — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Distinct from IntegerField purely in presentation (adds visible +/-
 * buttons and a step other than 1); validation semantics are the same.
 */
class NumericStepperField extends Field
{
    private ?int $min = null;

    private ?int $max = null;

    private int $step = 1;

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

    public function step(int $step): static
    {
        $clone = clone $this;
        $clone->step = $step;

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

    public function getStep(): int
    {
        return $this->step;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.numeric-stepper';
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
