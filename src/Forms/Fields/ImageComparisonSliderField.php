<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * A draggable divider comparing/selecting a point between two images —
 * Wave C (FORMS_FEATURE_PLAN.md Phase 3). Value is the divider position
 * as a percentage (0-100).
 */
class ImageComparisonSliderField extends Field
{
    private ?string $beforeImageUrl = null;

    private ?string $afterImageUrl = null;

    public function beforeImageUrl(string $url): static
    {
        $clone = clone $this;
        $clone->beforeImageUrl = $url;

        return $clone;
    }

    public function afterImageUrl(string $url): static
    {
        $clone = clone $this;
        $clone->afterImageUrl = $url;

        return $clone;
    }

    public function getBeforeImageUrl(): ?string
    {
        return $this->beforeImageUrl;
    }

    public function getAfterImageUrl(): ?string
    {
        return $this->afterImageUrl;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.image-comparison-slider';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'numeric';
        $rules[] = 'min:0';
        $rules[] = 'max:100';

        return $rules;
    }
}
