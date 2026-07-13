<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Text input with typeahead suggestions drawn from options() — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3). Unlike SelectField, the user types
 * freely; suggestions merely narrow/assist. For large/remote datasets use
 * LookupField instead.
 */
class AutocompleteField extends Field
{
    use HasOptions;

    public function getViewName(): string
    {
        return 'architect::forms.fields.autocomplete';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
