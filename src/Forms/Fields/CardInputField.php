<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Card entry via a payment provider's hosted fields — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Deliberately never handles raw card data server-side (PAN, CVC): the
 * value this field carries is the provider's returned token/reference
 * string only. providerScriptUrl() points at the provider's own
 * client-side SDK (e.g. Stripe.js) that renders the actual hosted
 * input elements inside this field's mount point.
 */
class CardInputField extends Field
{
    private ?string $providerScriptUrl = null;

    private ?string $publishableKey = null;

    public function providerScriptUrl(string $url): static
    {
        $clone = clone $this;
        $clone->providerScriptUrl = $url;

        return $clone;
    }

    public function publishableKey(string $key): static
    {
        $clone = clone $this;
        $clone->publishableKey = $key;

        return $clone;
    }

    public function getProviderScriptUrl(): ?string
    {
        return $this->providerScriptUrl;
    }

    public function getPublishableKey(): ?string
    {
        return $this->publishableKey;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.card-input';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
