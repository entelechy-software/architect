<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Drag-to-rank input: arrange options from most to least preferred —
 * Wave C (FORMS_FEATURE_PLAN.md Phase 3). Value is the array of option
 * keys in ranked order — functionally the same shape as SortableListField,
 * kept as a distinct class purely for semantic clarity in survey contexts.
 */
class RankingField extends Field
{
    use HasOptions;

    public function getViewName(): string
    {
        return 'architect::forms.fields.ranking';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
