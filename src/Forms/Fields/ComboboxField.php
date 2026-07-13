<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Combination text input + dropdown — Wave B (FORMS_FEATURE_PLAN.md
 * Phase 3). Unlike SelectField, allows entering a value not present in
 * options() (e.g. creating a new tag-like value inline).
 */
class ComboboxField extends Field
{
    use HasOptions;

    private bool $allowCustomValue = true;

    public function allowCustomValue(bool $allow = true): static
    {
        $clone = clone $this;
        $clone->allowCustomValue = $allow;

        return $clone;
    }

    public function isCustomValueAllowed(): bool
    {
        return $this->allowCustomValue;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.combobox';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        if (! $this->allowCustomValue && ! ($this->options instanceof \Closure) && $this->options !== []) {
            $rules[] = 'in:'.implode(',', array_keys($this->options));
        }

        return $rules;
    }
}
