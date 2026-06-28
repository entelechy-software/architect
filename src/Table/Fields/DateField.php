<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

/**
 * Date field rendered with Flatpickr.
 *
 * Posts to the server as 'd/m/Y'; data model converts to UTC DATETIME
 * for storage (per Task 1.3 datetime convention — UTC DATETIME, never
 * UNIX integers).
 */
class DateField extends ArchitectField
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
        return 'architect::table.fields.date';
    }

    public function validationRules(): array
    {
        $rules = parent::validationRules();
        $rules[] = 'date_format:d/m/Y';

        if ($this->mustBeAfter !== null) {
            $rules[] = "after:{$this->mustBeAfter}";
        }

        return $rules;
    }
}
