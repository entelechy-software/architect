<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Single-select button group (e.g. status: Draft | Published | Archived)
 * — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 */
class ButtonGroupField extends Field
{
    use HasOptions;

    public function getViewName(): string
    {
        return 'architect::forms.fields.button-group';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        if (! ($this->options instanceof \Closure) && $this->options !== []) {
            $rules[] = 'in:'.implode(',', array_keys($this->options));
        }

        return $rules;
    }
}
