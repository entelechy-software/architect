<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Date range input — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value shape: ['start' => 'd/m/Y', 'end' => 'd/m/Y'].
 *
 * Known limitation: getRules() validates the value as a 2-item array;
 * per-key (start/end date_format, ordering) validation is not expanded
 * into nested dot-notation rules by this field in Phase 3 — the engine's
 * current rule-building only supports one flat rule list per field name,
 * not per-nested-key expansion. Enforcing start-before-end, if needed, can
 * be added via a form-level ->rules() addition or a beforeSave() check
 * until nested field validation is addressed.
 */
class DateRangeField extends Field
{
    private ?string $minDate = null;

    private ?string $maxDate = null;

    public function minDate(string $date): static
    {
        $clone = clone $this;
        $clone->minDate = $date;

        return $clone;
    }

    public function maxDate(string $date): static
    {
        $clone = clone $this;
        $clone->maxDate = $date;

        return $clone;
    }

    public function getMinDate(): ?string
    {
        return $this->minDate;
    }

    public function getMaxDate(): ?string
    {
        return $this->maxDate;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.date-range';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';
        $rules[] = 'size:2';

        return $rules;
    }
}
