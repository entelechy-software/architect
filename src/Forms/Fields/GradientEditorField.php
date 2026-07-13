<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Multi-stop colour gradient editor — Wave C (FORMS_FEATURE_PLAN.md
 * Phase 3). Value shape: ['angle' => int, 'stops' => array<int,
 * array{color: string, position: float}>].
 */
class GradientEditorField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.gradient-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
