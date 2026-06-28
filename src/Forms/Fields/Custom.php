<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Escape hatch — renders an arbitrary host-app Blade view in place of a
 * built-in field type.
 */
class Custom extends Field
{
    private string $customView = '';

    /** @var array<string, mixed> */
    private array $viewData = [];

    public function view(string $view): static
    {
        $clone = clone $this;
        $clone->customView = $view;

        return $clone;
    }

    /** @param  array<string, mixed>  $data */
    public function viewData(array $data): static
    {
        $clone = clone $this;
        $clone->viewData = $data;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        return $this->viewData;
    }

    public function getViewName(): string
    {
        return $this->customView;
    }
}
