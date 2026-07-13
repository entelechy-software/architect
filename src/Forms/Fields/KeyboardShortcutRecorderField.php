<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Records a keyboard shortcut by listening for the next key combination
 * pressed — Wave D (FORMS_FEATURE_PLAN.md Phase 3). Value is the
 * normalized combo string, e.g. "cmd+shift+k".
 */
class KeyboardShortcutRecorderField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.keyboard-shortcut-recorder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
