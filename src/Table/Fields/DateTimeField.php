<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

/**
 * Date + time field rendered with Flatpickr (datetime mode).
 *
 * Posts to the server as 'd/m/Y H:i'; data model converts to UTC DATETIME
 * for storage (per Task 1.3 datetime convention — UTC DATETIME, never
 * UNIX integers).
 *
 * mustBeAfter() accepts a sibling field name and emits an `after:` rule
 * so the engine validates ordering (e.g. voting_end must be after voting_start).
 */
class DateTimeField extends ArchitectField
{
    private ?string $mustBeAfter = null;

    public function mustBeAfter(string $otherField): self
    {
        $clone = clone $this;
        $clone->mustBeAfter = $otherField;

        return $clone;
    }

    public function getMustBeAfter(): ?string
    {
        return $this->mustBeAfter;
    }

    public function blade(): string
    {
        return 'architect::table.fields.datetime';
    }

    public function validationRules(): array
    {
        $rules = parent::validationRules();
        $rules[] = 'date_format:d/m/Y H:i';

        if ($this->mustBeAfter !== null) {
            $rules[] = "after:{$this->mustBeAfter}";
        }

        return $rules;
    }
}
