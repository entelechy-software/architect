<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Password input with live strength feedback — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3). Strength scoring itself happens
 * client-side (see Blade view); server-side this enforces a configurable
 * minimum length and Laravel's own Password rule set.
 */
class PasswordStrengthField extends Field
{
    private int $minLength = 12;

    private bool $requireConfirmation = false;

    public function minLength(int $length): static
    {
        $clone = clone $this;
        $clone->minLength = $length;

        return $clone;
    }

    public function requireConfirmation(bool $require = true): static
    {
        $clone = clone $this;
        $clone->requireConfirmation = $require;

        return $clone;
    }

    public function getMinLength(): int
    {
        return $this->minLength;
    }

    public function isConfirmationRequired(): bool
    {
        return $this->requireConfirmation;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.password-strength';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';
        $rules[] = "min:{$this->minLength}";

        if ($this->requireConfirmation) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
