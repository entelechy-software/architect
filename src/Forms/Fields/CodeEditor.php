<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Monaco-backed code editor (loaded from CDN in dev; bundle for production
 * — see Phase 4 §4.4).
 */
class CodeEditor extends Field
{
    private string $language = 'plaintext';

    private string $height = '300px';

    private string $theme = 'vs-dark';

    /** @param  string  $language  php|js|json|... (any Monaco language id) */
    public function language(string $language): static
    {
        $clone = clone $this;
        $clone->language = $language;

        return $clone;
    }

    public function height(string $height): static
    {
        $clone = clone $this;
        $clone->height = $height;

        return $clone;
    }

    public function theme(string $theme): static
    {
        $clone = clone $this;
        $clone->theme = $theme;

        return $clone;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.code-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
