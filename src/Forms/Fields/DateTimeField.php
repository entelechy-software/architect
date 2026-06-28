<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Date + time field rendered with Flatpickr (datetime mode).
 *
 * Posts to the server as 'd/m/Y H:i'; the consuming application converts
 * to UTC DATETIME for storage — never UNIX integers.
 *
 * mustBeAfter() accepts a sibling field name and emits an `after:` rule
 * so the engine validates ordering (e.g. voting_end must be after voting_start).
 */
class DateTimeField extends Field
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
        return 'architect::forms.fields.datetime';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'date_format:d/m/Y H:i';

        if ($this->mustBeAfter !== null) {
            $rules[] = "after:{$this->mustBeAfter}";
        }

        return $rules;
    }
}
