<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Rotational dial/knob control for values that feel naturally rotational
 * (angle, gain, temperature) — Wave C (FORMS_FEATURE_PLAN.md Phase 3).
 * Functionally a numeric range; distinguished purely by presentation.
 */
class DialKnobField extends Field
{
    private float $min = 0.0;

    private float $max = 100.0;

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

    public function getMin(): float
    {
        return $this->min;
    }

    public function getMax(): float
    {
        return $this->max;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.dial-knob';
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
