<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Canvas where objects can be dragged, resized, and rotated — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Value shape: array<int, array{id:
 * string, x: float, y: float, width: float, height: float, rotation:
 * float}>.
 */
class CanvasManipulationField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.canvas-manipulation';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
