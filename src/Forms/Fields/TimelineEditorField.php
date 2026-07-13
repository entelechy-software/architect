<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Timeline editor: place/drag/resize labelled segments along a horizontal
 * axis (video chapters, schedule blocks, animation keyframes) — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Value shape: array<int, array{label:
 * string, start: float, end: float}>.
 */
class TimelineEditorField extends Field
{
    private float $totalDuration = 3600.0;

    public function totalDuration(float $seconds): static
    {
        $clone = clone $this;
        $clone->totalDuration = $seconds;

        return $clone;
    }

    public function getTotalDuration(): float
    {
        return $this->totalDuration;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.timeline-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
