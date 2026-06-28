<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Single boolean checkbox.
 */
class CheckboxField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.checkbox';
    }

    public function getRules(): array
    {
        // Unchecked checkboxes do not POST any value, so plain 'required'
        // would always fail — 'accepted' is the rule that actually means
        // "must be checked" (e.g. terms-of-service acceptance).
        return $this->isRequired() ? ['accepted'] : ['nullable', 'boolean'];
    }
}
