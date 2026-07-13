<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Draws bounding boxes/polygons/points/labels over a background image —
 * Wave C (FORMS_FEATURE_PLAN.md Phase 3). Value shape: array<int,
 * array{type: string, coordinates: array, label: ?string}>.
 */
class AnnotationField extends Field
{
    private ?string $imageUrl = null;

    public function imageUrl(string $url): static
    {
        $clone = clone $this;
        $clone->imageUrl = $url;

        return $clone;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.annotation';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
