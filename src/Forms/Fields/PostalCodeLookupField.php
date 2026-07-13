<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Postcode entry that looks up and lets the user select a matching
 * address — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value shape: ['postcode' => ..., 'selected' => array|null]. The lookup
 * provider is a host-app concern wired via lookupUrl().
 */
class PostalCodeLookupField extends Field
{
    private ?string $lookupUrl = null;

    public function lookupUrl(string $url): static
    {
        $clone = clone $this;
        $clone->lookupUrl = $url;

        return $clone;
    }

    public function getLookupUrl(): ?string
    {
        return $this->lookupUrl;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.postal-code-lookup';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
