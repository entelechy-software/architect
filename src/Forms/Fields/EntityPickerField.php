<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * A richer entity picker: each search result renders more than a plain
 * label (e.g. avatar, subtitle, status badge) — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Distinct from LookupField (this plan keeps LookupField's simpler
 * label-only AJAX contract unchanged); EntityPickerField is for richer,
 * templated result cards. Value is the selected record's key (typically
 * its id).
 */
class EntityPickerField extends Field
{
    private ?string $searchUrl = null;

    /** @var class-string|null */
    private ?string $model = null;

    private bool $multiple = false;

    public function searchUrl(string $url): static
    {
        $clone = clone $this;
        $clone->searchUrl = $url;

        return $clone;
    }

    /** @param  class-string  $model */
    public function model(string $model): static
    {
        $clone = clone $this;
        $clone->model = $model;

        return $clone;
    }

    public function multiple(bool $multiple = true): static
    {
        $clone = clone $this;
        $clone->multiple = $multiple;

        return $clone;
    }

    public function getSearchUrl(): ?string
    {
        return $this->searchUrl;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.entity-picker';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = $this->multiple ? 'array' : 'integer';

        return $rules;
    }
}
