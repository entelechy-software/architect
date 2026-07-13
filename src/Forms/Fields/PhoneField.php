<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * International phone number input — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Validates a loose E.164-shaped number (optional leading +, 7-15 digits).
 * defaultCountry() is presentational only in Phase 3 (informs a future
 * client-side country-code picker); it does not alter the validation
 * rule.
 */
class PhoneField extends Field
{
    private ?string $defaultCountry = null;

    public function defaultCountry(string $iso2): static
    {
        $clone = clone $this;
        $clone->defaultCountry = strtoupper($iso2);

        return $clone;
    }

    public function getDefaultCountry(): ?string
    {
        return $this->defaultCountry;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.phone';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';
        $rules[] = 'regex:/^\+?[1-9]\d{6,14}$/';

        return $rules;
    }
}
