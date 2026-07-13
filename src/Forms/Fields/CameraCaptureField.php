<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Photo capture via the device camera — Wave C (FORMS_FEATURE_PLAN.md
 * Phase 3). Extends FileUpload for accept/maxSize/disk handling; adds
 * only the camera-facing preference.
 */
class CameraCaptureField extends FileUpload
{
    private string $facing = 'environment';

    /** @param  string  $facing  'user' (front camera) or 'environment' (rear camera) */
    public function facing(string $facing): static
    {
        $clone = clone $this;
        $clone->facing = $facing;

        return $clone;
    }

    public function getFacing(): string
    {
        return $this->facing;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.camera-capture';
    }
}
