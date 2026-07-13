<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Structured HTTP request builder: method, URL, headers, query
 * parameters, body, authentication — Wave D (FORMS_FEATURE_PLAN.md
 * Phase 3). Value shape: ['method' => string, 'url' => string, 'headers'
 * => array<string, string>, 'query' => array<string, string>, 'body' =>
 * mixed, 'auth' => array|null].
 */
class ApiRequestBuilderField extends Field
{
    /** @var array<int, string> */
    private array $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /** @param  array<int, string>  $methods */
    public function methods(array $methods): static
    {
        $clone = clone $this;
        $clone->methods = $methods;

        return $clone;
    }

    /** @return array<int, string> */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.api-request-builder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
