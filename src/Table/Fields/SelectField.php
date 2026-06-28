<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

/**
 * Static-options select field.
 *
 * Use when the full set of choices is known at definition time.
 * For AJAX-driven or large remote datasets, use LookupField instead.
 *
 * ->options(['value' => 'Label', ...])
 */
class SelectField extends ArchitectField
{
    /** @var array<string|int, string> */
    private array $options = [];

    /**
     * @param  array<string|int, string>  $options  key => display label pairs
     */
    public function options(array $options): self
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    /** @return array<string|int, string> */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function blade(): string
    {
        return 'architect::table.fields.select';
    }

    public function validationRules(): array
    {
        $rules = parent::validationRules();

        if ($this->options !== []) {
            $keys = implode(',', array_keys($this->options));
            $rules[] = "in:{$keys}";
        }

        return $rules;
    }
}
