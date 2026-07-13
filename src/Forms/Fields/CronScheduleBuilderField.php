<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Builds a cron expression from a friendly schedule description (e.g.
 * "every weekday at 09:00") — Wave D (FORMS_FEATURE_PLAN.md Phase 3).
 * Value is the resulting 5-field cron expression string.
 */
class CronScheduleBuilderField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.cron-schedule-builder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';
        $rules[] = 'regex:/^(\S+\s+){4}\S+$/';

        return $rules;
    }
}
