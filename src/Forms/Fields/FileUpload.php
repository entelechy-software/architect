<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Drag & drop upload with progress bar, optionally accepting multiple files.
 */
class FileUpload extends Field
{
    private bool $multiple = false;

    private ?string $accept = null;

    private ?int $maxSizeKb = null;

    private string $disk = 'public';

    public function multiple(bool $multiple = true): static
    {
        $clone = clone $this;
        $clone->multiple = $multiple;

        return $clone;
    }

    public function accept(string $accept): static
    {
        $clone = clone $this;
        $clone->accept = $accept;

        return $clone;
    }

    public function maxSize(int $kilobytes): static
    {
        $clone = clone $this;
        $clone->maxSizeKb = $kilobytes;

        return $clone;
    }

    public function disk(string $disk): static
    {
        $clone = clone $this;
        $clone->disk = $disk;

        return $clone;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function getAccept(): ?string
    {
        return $this->accept;
    }

    public function getMaxSizeKb(): ?int
    {
        return $this->maxSizeKb;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.file-upload';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = $this->multiple ? 'array' : 'file';

        if ($this->maxSizeKb !== null) {
            $rules[] = "max:{$this->maxSizeKb}";
        }

        if ($this->accept !== null) {
            $rules[] = 'mimes:'.str_replace(['.', ' '], '', $this->accept);
        }

        return $rules;
    }
}
