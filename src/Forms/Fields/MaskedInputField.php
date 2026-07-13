<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Text input that enforces/displays a fixed format via an input mask
 * (e.g. sort code `__-__-__`, reference `AAA-000000`) — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * mask() uses simple placeholder tokens: `9` = digit, `A` = letter,
 * `*` = alphanumeric; any other character is a literal.
 */
class MaskedInputField extends Field
{
    private ?string $mask = null;

    public function mask(string $mask): static
    {
        $clone = clone $this;
        $clone->mask = $mask;

        return $clone;
    }

    public function getMask(): ?string
    {
        return $this->mask;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.masked-input';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        if ($this->mask !== null) {
            $pattern = preg_replace(['/9/', '/A/', '/\*/'], ['\d', '[A-Za-z]', '[A-Za-z0-9]'], preg_quote($this->mask, '/'));
            $rules[] = "regex:/^{$pattern}$/";
        }

        return $rules;
    }
}
