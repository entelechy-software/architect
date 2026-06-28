<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

/**
 * Read-only label / value pair rendered in the form.
 *
 * Posts no value back to the server. Used for showing computed or
 * contextual data (e.g. "Created: 12 May 2026") inside the form panel.
 */
class DisplayField extends ArchitectField
{
    public function blade(): string
    {
        return 'architect::table.fields.display';
    }

    public function validationRules(): array
    {
        // No value is posted; nothing to validate.
        return [];
    }
}
