<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

class CheckboxField extends ArchitectField
{
    public function blade(): string
    {
        return 'architect::table.fields.checkbox';
    }

    public function validationRules(): array
    {
        // Unchecked checkboxes do not POST any value; treat as boolean
        // with optional default false applied by the data model.
        return ['nullable', 'boolean'];
    }
}
