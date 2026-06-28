<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * WYSIWYG editor backed by TipTap (loaded from CDN in dev; bundle via
 * packages/architect/resources/js for production — see Phase 4 §4.4).
 */
class RichEditor extends Field
{
    /** @var array<int, string> */
    private array $toolbar = ['heading', 'bold', 'italic', 'bulletList', 'orderedList', 'link', 'image'];

    /** @param  array<int, string>  $toolbar */
    public function toolbar(array $toolbar): static
    {
        $clone = clone $this;
        $clone->toolbar = $toolbar;

        return $clone;
    }

    /** @return array<int, string> */
    public function getToolbar(): array
    {
        return $this->toolbar;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.rich-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
