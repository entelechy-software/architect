<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Dropdown;

use Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem;

/**
 * A labelled group of plain action links inside a ToolbarDropdown.
 *
 * Renders as a section with an optional heading followed by a list of
 * anchor links. Useful for navigation-style dropdown sections.
 *
 * Example:
 *   DropdownLinkGroup::make('quick-nav')
 *       ->label('Jump to')
 *       ->link('Cases', '/advice/cases')
 *       ->link('Categories', '/advice/categories')
 */
final class DropdownLinkGroup implements DropdownItem
{
    private ?string $label = null;

    /** @var list<array{label: string, url: string, icon: string|null, newWindow: bool}> */
    private array $links = [];

    private function __construct(private readonly string $itemKey) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function label(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function link(string $label, string $url, ?string $icon = null, bool $newWindow = false): self
    {
        $clone = clone $this;
        $clone->links[] = [
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
            'newWindow' => $newWindow,
        ];

        return $clone;
    }

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'link-group';
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /** @return list<array{label: string, url: string, icon: string|null, newWindow: bool}> */
    public function getLinks(): array
    {
        return $this->links;
    }
}
