<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Structured query-language text input (e.g. `status:active AND
 * created_at:>2026-01-01`) with syntax highlighting/autocomplete hooks —
 * Wave B (FORMS_FEATURE_PLAN.md Phase 3). Parsing/execution of the query
 * language itself is a host-app concern; this field only captures and
 * validates the raw text.
 */
class QueryLanguageTextField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.query-language-text';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
