<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Date field rendered with Flatpickr.
 *
 * Posts to the server as 'd/m/Y'; the consuming application converts to
 * UTC DATETIME for storage — never UNIX integers.
 */
class DateField extends Field
{
    private ?string $mustBeAfter = null;

    public function mustBeAfter(string $otherField): static
    {
        $clone = clone $this;
        $clone->mustBeAfter = $otherField;

        return $clone;
    }

    public function getMustBeAfter(): ?string
    {
        return $this->mustBeAfter;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.date';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'date_format:d/m/Y';

        if ($this->mustBeAfter !== null) {
            $rules[] = "after:{$this->mustBeAfter}";
        }

        return $rules;
    }
}
