<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Rich text editor supporting @mentions of people/channels/records —
 * Wave C (FORMS_FEATURE_PLAN.md Phase 3). Value is the rendered
 * text/HTML string with mention tokens embedded; resolving a mentionable
 * entity list is a host-app concern wired via mentionableUrl().
 */
class MentionEditorField extends Field
{
    private ?string $mentionableUrl = null;

    public function mentionableUrl(string $url): static
    {
        $clone = clone $this;
        $clone->mentionableUrl = $url;

        return $clone;
    }

    public function getMentionableUrl(): ?string
    {
        return $this->mentionableUrl;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.mention-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
