<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Free-hand drawing/sketch canvas (pen, highlighter, shapes, text,
 * eraser) — Wave C (FORMS_FEATURE_PLAN.md Phase 3). Value is a data-URL
 * encoded image, or a serialized stroke history if strokeFormat() is
 * enabled — kept as a plain string either way.
 */
class DrawingSketchField extends Field
{
    private bool $strokeFormat = false;

    public function strokeFormat(bool $enabled = true): static
    {
        $clone = clone $this;
        $clone->strokeFormat = $enabled;

        return $clone;
    }

    public function usesStrokeFormat(): bool
    {
        return $this->strokeFormat;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.drawing-sketch';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
