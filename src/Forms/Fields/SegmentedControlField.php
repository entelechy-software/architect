<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Small number of mutually exclusive choices rendered as a segmented
 * control — Wave B (FORMS_FEATURE_PLAN.md Phase 3). Functionally
 * equivalent to Radio; distinguished purely by presentation.
 */
class SegmentedControlField extends Field
{
    use HasOptions;

    public function getViewName(): string
    {
        return 'architect::forms.fields.segmented-control';
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
