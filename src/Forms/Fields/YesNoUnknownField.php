<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Tri-state control for a nullable boolean that has genuine meaning in its
 * "unknown" state (distinct from a plain checkbox) — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3). Value is 'yes' | 'no' | 'unknown'.
 */
class YesNoUnknownField extends Field
{
    private string $yesLabel = 'Yes';

    private string $noLabel = 'No';

    private string $unknownLabel = 'Not known';

    public function labels(string $yes, string $no, string $unknown): static
    {
        $clone = clone $this;
        $clone->yesLabel = $yes;
        $clone->noLabel = $no;
        $clone->unknownLabel = $unknown;

        return $clone;
    }

    public function getYesLabel(): string
    {
        return $this->yesLabel;
    }

    public function getNoLabel(): string
    {
        return $this->noLabel;
    }

    public function getUnknownLabel(): string
    {
        return $this->unknownLabel;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.yes-no-unknown';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'in:yes,no,unknown';

        return $rules;
    }
}
