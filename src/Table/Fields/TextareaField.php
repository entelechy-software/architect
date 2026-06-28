<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

class TextareaField extends ArchitectField
{
    private int $rows = 4;

    private ?int $maxLength = null;

    private bool $showCharCount = false;

    public function rows(int $rows): self
    {
        $clone = clone $this;
        $clone->rows = $rows;

        return $clone;
    }

    public function maxLength(int $length): self
    {
        $clone = clone $this;
        $clone->maxLength = $length;

        return $clone;
    }

    public function showCharCount(bool $show = true): self
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

    public function blade(): string
    {
        return 'architect::table.fields.textarea';
    }

    public function validationRules(): array
    {
        $rules = parent::validationRules();
        $rules[] = 'string';

        if ($this->maxLength !== null) {
            $rules[] = "max:{$this->maxLength}";
        }

        return $rules;
    }
}
