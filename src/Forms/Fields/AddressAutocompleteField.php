<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Address search-as-you-type that resolves to a structured address —
 * Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value shape: ['line1' => ..., 'line2' => ..., 'city' => ...,
 * 'postcode' => ..., 'country' => ...]. The actual geocoding/address
 * lookup provider is a host-app concern wired via searchUrl().
 */
class AddressAutocompleteField extends Field
{
    private ?string $searchUrl = null;

    public function searchUrl(string $url): static
    {
        $clone = clone $this;
        $clone->searchUrl = $url;

        return $clone;
    }

    public function getSearchUrl(): ?string
    {
        return $this->searchUrl;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.address-autocomplete';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
