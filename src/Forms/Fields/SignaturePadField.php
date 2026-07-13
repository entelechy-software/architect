<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Canvas-based signature capture (mouse/touch/stylus) — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Value is a data-URL encoded PNG
 * string.
 */
class SignaturePadField extends Field
{
    private string $penColor = '#000000';

    private string $backgroundColor = '#ffffff';

    public function penColor(string $color): static
    {
        $clone = clone $this;
        $clone->penColor = $color;

        return $clone;
    }

    public function backgroundColor(string $color): static
    {
        $clone = clone $this;
        $clone->backgroundColor = $color;

        return $clone;
    }

    public function getPenColor(): string
    {
        return $this->penColor;
    }

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.signature-pad';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
