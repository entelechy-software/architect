<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Structured recurrence rule builder — Wave B (FORMS_FEATURE_PLAN.md
 * Phase 3). Value shape: ['freq' => 'daily'|'weekly'|'monthly'|'yearly',
 * 'interval' => int, 'by_day' => array<int,string>, 'until' => ?string].
 */
class RecurrenceBuilderField extends Field
{
    /** @var array<int, string> */
    private array $frequencies = ['daily', 'weekly', 'monthly', 'yearly'];

    /** @param  array<int, string>  $frequencies */
    public function frequencies(array $frequencies): static
    {
        $clone = clone $this;
        $clone->frequencies = $frequencies;

        return $clone;
    }

    /** @return array<int, string> */
    public function getFrequencies(): array
    {
        return $this->frequencies;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.recurrence-builder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
