<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

/**
 * AJAX-driven lookup field. Posts back as {val, txt} from the client; the
 * data model layer is expected to coerce this to a scalar id when persisting.
 */
class LookupField extends ArchitectField
{
    private ?string $sourceUrl = null;

    private ?string $cascadeFromField = null;

    private bool $multi = false;

    public function source(string $url): self
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
    public function cascadeFrom(string $parentField): self
    {
        $clone = clone $this;
        $clone->cascadeFromField = $parentField;

        return $clone;
    }

    public function multi(bool $multi = true): self
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

    public function blade(): string
    {
        return 'architect::table.fields.lookup';
    }

    public function validationRules(): array
    {
        // Lookup fields post {val, txt} arrays; the engine coerces them to a
        // scalar before validation runs, so we validate the scalar id here.
        $rules = parent::validationRules();
        $rules[] = 'integer';

        return $rules;
    }
}
