<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Image upload with an in-browser crop/rotate/zoom step before submit —
 * Wave B (FORMS_FEATURE_PLAN.md Phase 3). Extends FileUpload for its
 * accept/maxSize/disk handling; adds only the aspect-ratio constraint the
 * client-side cropper widget enforces.
 */
class ImageCropperField extends FileUpload
{
    private ?float $aspectRatio = null;

    public function aspectRatio(float $ratio): static
    {
        $clone = clone $this;
        $clone->aspectRatio = $ratio;

        return $clone;
    }

    public function getAspectRatio(): ?float
    {
        return $this->aspectRatio;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.image-cropper';
    }
}
