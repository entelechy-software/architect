<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Full page/layout builder — Wave C (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Extends the existing Builder/Block system in place rather than
 * introducing a parallel block editor: BlockEditor *is* a Builder,
 * distinguished only by defaulting to a layout-oriented presentation
 * (e.g. a canvas with draggable sections) instead of Builder's linear
 * stacked-blocks presentation.
 */
class BlockEditor extends Builder
{
    public function getViewName(): string
    {
        return 'architect::forms.fields.block-editor';
    }
}
