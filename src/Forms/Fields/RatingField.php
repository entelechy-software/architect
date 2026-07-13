<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Star/numeric rating input — Wave C (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value is an integer from 1 to max() (default 5).
 */
class RatingField extends Field
{
    private int $max = 5;

    public function max(int $max): static
    {
        $clone = clone $this;
        $clone->max = $max;

        return $clone;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.rating';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'integer';
        $rules[] = 'min:1';
        $rules[] = "max:{$this->max}";

        return $rules;
    }
}
