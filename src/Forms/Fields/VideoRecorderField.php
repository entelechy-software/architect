<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Video recording via the device camera + microphone — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Extends FileUpload for
 * accept/maxSize/disk handling; adds facing preference and a maximum
 * recording duration.
 */
class VideoRecorderField extends FileUpload
{
    private string $facing = 'environment';

    private ?int $maxDurationSeconds = null;

    public function facing(string $facing): static
    {
        $clone = clone $this;
        $clone->facing = $facing;

        return $clone;
    }

    public function maxDurationSeconds(int $seconds): static
    {
        $clone = clone $this;
        $clone->maxDurationSeconds = $seconds;

        return $clone;
    }

    public function getFacing(): string
    {
        return $this->facing;
    }

    public function getMaxDurationSeconds(): ?int
    {
        return $this->maxDurationSeconds;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.video-recorder';
    }
}
