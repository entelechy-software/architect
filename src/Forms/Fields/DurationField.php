<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Duration input — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value is the total number of minutes (an integer), regardless of how
 * many unit inputs (hours/minutes) the Blade view renders.
 */
class DurationField extends Field
{
    private ?int $maxMinutes = null;

    public function maxMinutes(int $minutes): static
    {
        $clone = clone $this;
        $clone->maxMinutes = $minutes;

        return $clone;
    }

    public function getMaxMinutes(): ?int
    {
        return $this->maxMinutes;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.duration';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'integer';
        $rules[] = 'min:0';

        if ($this->maxMinutes !== null) {
            $rules[] = "max:{$this->maxMinutes}";
        }

        return $rules;
    }
}
