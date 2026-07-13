<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Fixed-length numeric one-time-password / PIN input — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3).
 */
class OtpField extends Field
{
    private int $length = 6;

    public function length(int $length): static
    {
        $clone = clone $this;
        $clone->length = $length;

        return $clone;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.otp';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';
        $rules[] = "size:{$this->length}";
        $rules[] = 'regex:/^\d+$/';

        return $rules;
    }
}
