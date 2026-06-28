<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Multi-line text input.
 */
class TextareaField extends Field
{
    private int $rows = 4;

    private ?int $maxLength = null;

    private bool $showCharCount = false;

    public function rows(int $rows): static
    {
        $clone = clone $this;
        $clone->rows = $rows;

        return $clone;
    }

    public function maxLength(int $length): static
    {
        $clone = clone $this;
        $clone->maxLength = $length;

        return $clone;
    }

    public function showCharCount(bool $show = true): static
    {
        $clone = clone $this;
        $clone->showCharCount = $show;

        return $clone;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function getShowCharCount(): bool
    {
        return $this->showCharCount;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.textarea';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        if ($this->maxLength !== null) {
            $rules[] = "max:{$this->maxLength}";
        }

        return $rules;
    }
}
