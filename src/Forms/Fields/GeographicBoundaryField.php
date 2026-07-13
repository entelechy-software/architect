<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Draws a polygon/rectangle/circle boundary on a map — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Value shape: GeoJSON-like
 * ['type' => 'Polygon'|'Circle'|..., 'coordinates' => array].
 */
class GeographicBoundaryField extends Field
{
    private ?string $providerScriptUrl = null;

    public function providerScriptUrl(string $url): static
    {
        $clone = clone $this;
        $clone->providerScriptUrl = $url;

        return $clone;
    }

    public function getProviderScriptUrl(): ?string
    {
        return $this->providerScriptUrl;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.geographic-boundary';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
