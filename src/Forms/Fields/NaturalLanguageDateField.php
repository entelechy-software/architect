<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Free-text input intended to be parsed into a structured date/recurrence
 * server-side (e.g. "next Friday at 2pm") — Wave B (FORMS_FEATURE_PLAN.md
 * Phase 3).
 *
 * Phase 3 ships the field and its raw-text validation only; the actual
 * natural-language parsing engine is a host-app or later-phase concern
 * (the reference document explicitly calls for the interpreted result to
 * remain visible/correctable, which this field's Blade view surfaces via
 * a `data-parsed-preview` slot for the host app's parser to populate).
 */
class NaturalLanguageDateField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.natural-language-date';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
