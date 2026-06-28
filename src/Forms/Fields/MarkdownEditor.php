<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;

/**
 * Plain textarea with a formatting toolbar and a live split preview.
 */
class MarkdownEditor extends Field
{
    /** @var array<int, string> */
    private array $toolbar = ['bold', 'italic', 'link', 'bulletList', 'orderedList', 'codeBlock'];

    private ?Closure $previewUsing = null;

    /** @param  array<int, string>  $toolbar */
    public function toolbar(array $toolbar): static
    {
        $clone = clone $this;
        $clone->toolbar = $toolbar;

        return $clone;
    }

    public function previewUsing(Closure $callback): static
    {
        $clone = clone $this;
        $clone->previewUsing = $callback;

        return $clone;
    }

    /** @return array<int, string> */
    public function getToolbar(): array
    {
        return $this->toolbar;
    }

    public function renderPreview(string $value): string
    {
        return $this->previewUsing ? ($this->previewUsing)($value) : nl2br(e($value));
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.markdown-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
