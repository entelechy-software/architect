<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Groups a set of fields under a titled, optionally collapsible container.
 *
 * Renders as <div class="arch-form__section">; see resources/views/forms/section.blade.php.
 */
final class Section implements StructureItem
{
    private string $title = '';

    private ?string $description = null;

    private bool $collapsible = false;

    private bool $collapsed = false;

    /** @var array<int, StructureItem> */
    private array $structure = [];

    private function __construct() {}

    public static function make(string $title = ''): static
    {
        $instance = new self;
        $instance->title = $title;

        return $instance;
    }

    public function description(string $description): static
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function collapsible(bool $collapsible = true): static
    {
        $clone = clone $this;
        $clone->collapsible = $collapsible;

        return $clone;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $clone = clone $this;
        $clone->collapsed = $collapsed;

        return $clone;
    }

    /** @param  array<int, StructureItem>  $items */
    public function structure(array $items): static
    {
        $clone = clone $this;
        $clone->structure = $items;

        return $clone;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    /** @return array<int, StructureItem> */
    public function getStructure(): array
    {
        return $this->structure;
    }
}
