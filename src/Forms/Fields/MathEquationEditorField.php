<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Visual entry for fractions, powers, roots, and symbols — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Value is the resulting LaTeX string.
 */
class MathEquationEditorField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.math-equation-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
