<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Drags start/end handles over an audio waveform to select a segment —
 * Wave C (FORMS_FEATURE_PLAN.md Phase 3). Value shape: ['start' => float,
 * 'end' => float] (seconds).
 */
class AudioWaveformSelectionField extends Field
{
    private ?string $audioUrl = null;

    public function audioUrl(string $url): static
    {
        $clone = clone $this;
        $clone->audioUrl = $url;

        return $clone;
    }

    public function getAudioUrl(): ?string
    {
        return $this->audioUrl;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.audio-waveform-selection';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
