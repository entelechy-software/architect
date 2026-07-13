<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Map-based location picker (search, drop/drag a marker, select
 * coordinates) — Wave C (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value shape: ['lat' => float, 'lng' => float]. The mapping provider
 * (Leaflet, Google Maps, Mapbox, ...) is a host-app concern wired via
 * providerScriptUrl(); this field only carries the resulting coordinates.
 */
class MapLocationField extends Field
{
    private ?string $providerScriptUrl = null;

    private int $defaultZoom = 12;

    public function providerScriptUrl(string $url): static
    {
        $clone = clone $this;
        $clone->providerScriptUrl = $url;

        return $clone;
    }

    public function defaultZoom(int $zoom): static
    {
        $clone = clone $this;
        $clone->defaultZoom = $zoom;

        return $clone;
    }

    public function getProviderScriptUrl(): ?string
    {
        return $this->providerScriptUrl;
    }

    public function getDefaultZoom(): int
    {
        return $this->defaultZoom;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.map-location';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
