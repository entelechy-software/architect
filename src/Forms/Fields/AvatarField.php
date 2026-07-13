<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Avatar upload: circular preview, fixed aspect ratio, crop/reposition,
 * initials fallback — Wave B (FORMS_FEATURE_PLAN.md Phase 3). Extends
 * FileUpload rather than duplicating its accept/maxSize/disk handling;
 * only adds avatar-specific presentation options.
 */
class AvatarField extends FileUpload
{
    private string $initialsFrom = 'name';

    public function initialsFrom(string $field): static
    {
        $clone = clone $this;
        $clone->initialsFrom = $field;

        return $clone;
    }

    public function getInitialsFrom(): string
    {
        return $this->initialsFrom;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.avatar';
    }
}
