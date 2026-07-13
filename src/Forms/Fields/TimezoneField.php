<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Searchable IANA timezone picker — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 * Options are sourced from PHP's own timezone database
 * (DateTimeZone::listIdentifiers()) — no hardcoded/stale list to maintain.
 */
class TimezoneField extends Field
{
    /** @return array<int, string> */
    public function getTimezones(): array
    {
        return \DateTimeZone::listIdentifiers();
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.timezone';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';
        $rules[] = 'timezone';

        return $rules;
    }
}
