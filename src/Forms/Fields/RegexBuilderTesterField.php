<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Regex pattern builder/tester with live match highlighting and captured
 * groups — Wave D (FORMS_FEATURE_PLAN.md Phase 3). Value shape:
 * ['pattern' => string, 'flags' => string].
 */
class RegexBuilderTesterField extends Field
{
    private ?string $sampleText = null;

    public function sampleText(string $text): static
    {
        $clone = clone $this;
        $clone->sampleText = $text;

        return $clone;
    }

    public function getSampleText(): ?string
    {
        return $this->sampleText;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.regex-builder-tester';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
