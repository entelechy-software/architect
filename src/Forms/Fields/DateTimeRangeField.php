<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Date + time range input — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value shape: ['start' => 'd/m/Y H:i', 'end' => 'd/m/Y H:i']. See
 * DateRangeField's docblock for the same nested-validation limitation.
 */
class DateTimeRangeField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.datetime-range';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';
        $rules[] = 'size:2';

        return $rules;
    }
}
