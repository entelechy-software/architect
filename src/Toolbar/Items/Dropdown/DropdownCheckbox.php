<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Dropdown;

use Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem;

/**
 * A boolean toggle item inside a ToolbarDropdown.
 *
 * Two visual styles share the same API and state management:
 *   - Checkbox style (default): standard checkbox on the left of the label row.
 *   - Toggle/pill style: iOS-style pill switch. Use the ::toggle() factory.
 *
 * Both manage boolean state via ToolbarEngine::$checkboxValues.
 * Dispatches architect:toolbar:checkbox-changed { toolbarKey, itemKey, value }.
 *
 * Example — checkbox style:
 *   DropdownCheckbox::make('show-notes')
 *       ->label('Show notes column')
 *       ->default(true)
 *       ->persist('local')
 *       ->dispatchOnChange('cases:column-toggle', ['column' => 'notes'])
 *
 * Example — toggle/pill style:
 *   DropdownCheckbox::toggle('show-archived')
 *       ->label('Show archived')
 *       ->dispatchOnChange('architect:filter-toggle', ['key' => 'archived'])
 */
final class DropdownCheckbox implements DropdownItem
{
    private string $label = '';

    private ?string $icon = null;

    private bool $defaultValue = false;

    private bool $disabled = false;

    private ?string $permission = null;

    private string $persist = 'none';

    private ?string $changeEvent = null;

    /** @var array<string, mixed> */
    private array $changePayload = [];

    /** Whether to render as a pill/toggle switch instead of a standard checkbox. */
    private bool $renderAsToggle = false;

    private function __construct(private readonly string $itemKey) {}

    /** Create a checkbox-style boolean item. */
    public static function make(string $key): self
    {
        return new self($key);
    }

    /** Create a toggle/pill-switch-style boolean item (same API as make()). */
    public static function toggle(string $key): self
    {
        $instance = new self($key);
        $instance->renderAsToggle = true;

        return $instance;
    }

    // ── Chainable setters ─────────────────────────────────────────────────────

    public function label(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    public function default(bool $value): self
    {
        $clone = clone $this;
        $clone->defaultValue = $value;

        return $clone;
    }

    public function disabled(bool $disabled = true): self
    {
        $clone = clone $this;
        $clone->disabled = $disabled;

        return $clone;
    }

    public function permission(string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    /**
     * @param  'none'|'local'|'url'  $strategy
     */
    public function persist(string $strategy): self
    {
        $clone = clone $this;
        $clone->persist = $strategy;

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchOnChange(string $event, array $payload = []): self
    {
        $clone = clone $this;
        $clone->changeEvent = $event;
        $clone->changePayload = $payload;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'checkbox';
    }

    /** Whether this item renders as a pill/toggle switch rather than a standard checkbox. */
    public function isToggle(): bool
    {
        return $this->renderAsToggle;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getDefault(): bool
    {
        return $this->defaultValue;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getPersist(): string
    {
        return $this->persist;
    }

    public function getChangeEvent(): ?string
    {
        return $this->changeEvent;
    }

    /** @return array<string, mixed> */
    public function getChangePayload(): array
    {
        return $this->changePayload;
    }
}
