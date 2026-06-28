<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * AJAX-driven lookup field. Posts back as {val, txt} from the client; the
 * consuming application is expected to coerce this to a scalar id when
 * persisting.
 */
class LookupField extends Field
{
    private ?string $sourceUrl = null;

    private ?string $cascadeFromField = null;

    private bool $multi = false;

    public function source(string $url): static
    {
        $clone = clone $this;
        $clone->sourceUrl = $url;

        return $clone;
    }

    /**
     * Cascade filter: when the parent field's value changes, this field's
     * AJAX source receives the parent value as a `cascade` query parameter.
     * Server endpoint must filter results accordingly.
     */
    public function cascadeFrom(string $parentField): static
    {
        $clone = clone $this;
        $clone->cascadeFromField = $parentField;

        return $clone;
    }

    public function multi(bool $multi = true): static
    {
        $clone = clone $this;
        $clone->multi = $multi;

        return $clone;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function getCascadeFromField(): ?string
    {
        return $this->cascadeFromField;
    }

    public function isMulti(): bool
    {
        return $this->multi;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.lookup';
    }

    public function getRules(): array
    {
        // Lookup fields post {val, txt} arrays; the engine coerces them to a
        // scalar before validation runs, so we validate the scalar id here.
        $rules = parent::getRules();
        $rules[] = 'integer';

        return $rules;
    }
}
