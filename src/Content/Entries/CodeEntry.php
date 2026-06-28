<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Entries;

/**
 * Renders the resolved value as a read-only, optionally syntax-highlighted
 * code block.
 *
 * Usage:
 *   CodeEntry::make('payload')->language('json')->copyable()->lineNumbers()
 */
class CodeEntry extends Entry
{
    protected string $language = 'plaintext';

    protected bool $copyable = false;

    protected bool $lineNumbers = false;

    public function language(string $language): static
    {
        $clone = clone $this;
        $clone->language = $language;

        return $clone;
    }

    public function copyable(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->copyable = $condition;

        return $clone;
    }

    public function lineNumbers(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->lineNumbers = $condition;

        return $clone;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function isCopyable(): bool
    {
        return $this->copyable;
    }

    public function hasLineNumbers(): bool
    {
        return $this->lineNumbers;
    }

    public function getViewName(): string
    {
        return 'architect::content.entries.code';
    }
}
