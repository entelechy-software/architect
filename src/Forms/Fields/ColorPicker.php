<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Hex/RGB/HSL color picker popover.
 */
class ColorPicker extends Field
{
    private bool $withAlpha = false;

    private string $format = 'hex';

    public function withAlpha(bool $withAlpha = true): static
    {
        $clone = clone $this;
        $clone->withAlpha = $withAlpha;

        return $clone;
    }

    /** @param  string  $format  hex|rgb|hsl */
    public function format(string $format): static
    {
        $clone = clone $this;
        $clone->format = $format;

        return $clone;
    }

    public function getWithAlpha(): bool
    {
        return $this->withAlpha;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.color-picker';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
