<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items;

use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;

/**
 * A visually connected strip of ToolbarButton instances (and optionally
 * ToolbarDropdown items). Renders as a joined button group with shared borders.
 *
 * Supports mixed types — buttons and dropdowns can coexist in the same group.
 *
 * Example:
 *   ToolbarButtonGroup::make('view-controls')
 *       ->add(ToolbarButton::make('list')->label('List')->icon('fas fa-list'))
 *       ->add(ToolbarButton::make('card')->label('Cards')->icon('fas fa-th'))
 *       ->position('left')
 */
final class ToolbarButtonGroup implements ToolbarItem
{
    /** @var list<ToolbarButton|ToolbarDropdown> */
    private array $items = [];

    private string $pos = 'left';

    private bool $disabled = false;

    /**
     * Size override applied to every button in this group, distinct from
     * each ToolbarButton's own size (which doesn't currently have one of
     * its own — see resources/views/toolbar/partials/button.blade.php,
     * which always renders at 'sm').
     *
     * @var 'sm'|'md'
     */
    private string $size = 'sm';

    private function __construct(private readonly string $itemKey) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function add(ToolbarButton|ToolbarDropdown $item): self
    {
        $clone = clone $this;
        $clone->items[] = $item;

        return $clone;
    }

    public function position(string $position): self
    {
        $clone = clone $this;
        $clone->pos = $position;

        return $clone;
    }

    public function disabled(bool $disabled = true): self
    {
        $clone = clone $this;
        $clone->disabled = $disabled;

        return $clone;
    }

    /** @param  'sm'|'md'  $size */
    public function size(string $size): self
    {
        $clone = clone $this;
        $clone->size = $size;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'button-group';
    }

    public function getPosition(): string
    {
        return $this->pos;
    }

    /** @return list<ToolbarButton|ToolbarDropdown> */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getSize(): string
    {
        return $this->size;
    }
}
