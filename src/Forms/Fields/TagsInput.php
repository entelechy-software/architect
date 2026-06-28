<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;

/**
 * Free-text tags input with optional autocomplete suggestions.
 */
class TagsInput extends Field
{
    /** @var array<int, string>|Closure */
    private array|Closure $suggestions = [];

    private bool $allowCreate = true;

    /** @param  array<int, string>|Closure  $suggestions */
    public function suggestions(array|Closure $suggestions): static
    {
        $clone = clone $this;
        $clone->suggestions = $suggestions;

        return $clone;
    }

    public function allowCreate(bool $allow = true): static
    {
        $clone = clone $this;
        $clone->allowCreate = $allow;

        return $clone;
    }

    /** @return array<int, string> */
    public function getSuggestions(): array
    {
        return $this->suggestions instanceof Closure ? ($this->suggestions)() : $this->suggestions;
    }

    public function getAllowCreate(): bool
    {
        return $this->allowCreate;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.tags-input';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
