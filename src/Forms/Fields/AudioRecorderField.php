<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Audio recording via the device microphone — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Extends FileUpload for
 * accept/maxSize/disk handling; adds only a maximum recording duration.
 */
class AudioRecorderField extends FileUpload
{
    private ?int $maxDurationSeconds = null;

    public function maxDurationSeconds(int $seconds): static
    {
        $clone = clone $this;
        $clone->maxDurationSeconds = $seconds;

        return $clone;
    }

    public function getMaxDurationSeconds(): ?int
    {
        return $this->maxDurationSeconds;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.audio-recorder';
    }
}
