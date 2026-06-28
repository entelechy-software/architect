<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Passes data through without rendering any visible UI.
 */
class Hidden extends Field
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.hidden';
    }
}
