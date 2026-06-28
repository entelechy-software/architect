<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Read-only label / value pair rendered in the form.
 *
 * Posts no value back to the server. Used for showing computed or
 * contextual data (e.g. "Created: 12 May 2026") inside the form.
 */
class DisplayField extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.display';
    }

    public function getRules(): array
    {
        // No value is posted; nothing to validate.
        return [];
    }
}
