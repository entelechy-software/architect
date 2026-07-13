<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Drag-to-reorder list — Wave C (FORMS_FEATURE_PLAN.md Phase 3). Value is
 * the array of option keys in the user's chosen order.
 */
class SortableListField extends Field
{
    use HasOptions;

    public function getViewName(): string
    {
        return 'architect::forms.fields.sortable-list';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
