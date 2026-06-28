<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Boolean on/off switch, renders as a pill toggle.
 */
class Toggle extends Field
{
    private string $onLabel = 'On';

    private string $offLabel = 'Off';

    public function onLabel(string $label): static
    {
        $clone = clone $this;
        $clone->onLabel = $label;

        return $clone;
    }

    public function offLabel(string $label): static
    {
        $clone = clone $this;
        $clone->offLabel = $label;

        return $clone;
    }

    public function getOnLabel(): string
    {
        return $this->onLabel;
    }

    public function getOffLabel(): string
    {
        return $this->offLabel;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.toggle';
    }

    public function getRules(): array
    {
        return ['nullable', 'boolean'];
    }
}
