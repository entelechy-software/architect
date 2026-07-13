<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Transfer-list (dual listbox) selection — Wave B (FORMS_FEATURE_PLAN.md
 * Phase 3). Available items move between two panes; value is the array of
 * selected keys.
 */
class DualListboxField extends Field
{
    use HasOptions;

    private string $availableLabel = 'Available';

    private string $selectedLabel = 'Selected';

    public function availableLabel(string $label): static
    {
        $clone = clone $this;
        $clone->availableLabel = $label;

        return $clone;
    }

    public function selectedLabel(string $label): static
    {
        $clone = clone $this;
        $clone->selectedLabel = $label;

        return $clone;
    }

    public function getAvailableLabel(): string
    {
        return $this->availableLabel;
    }

    public function getSelectedLabel(): string
    {
        return $this->selectedLabel;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.dual-listbox';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        if (! ($this->options instanceof \Closure) && $this->options !== []) {
            $rules[] = 'in:'.implode(',', array_keys($this->options));
        }

        return $rules;
    }
}
