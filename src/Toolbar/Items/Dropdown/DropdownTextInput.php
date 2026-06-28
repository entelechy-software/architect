<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Dropdown;

use Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem;

/**
 * An inline text input inside a ToolbarDropdown.
 *
 * Renders as a labelled text field within the dropdown panel. Input changes
 * are debounced (default 400 ms) and call ToolbarEngine::setTextValue().
 * State stored in ToolbarEngine::$textValues['dropdownKey.inputKey'].
 * Dispatches architect:toolbar:text-changed { toolbarKey, itemKey, value }.
 *
 * Example:
 *   DropdownTextInput::make('min-amount')
 *       ->label('Minimum amount')
 *       ->placeholder('0.00')
 *       ->type('number')
 *       ->default('')
 *       ->persist('local')
 *       ->debounce(400)
 *       ->dispatchOnChange('cases:min-amount-changed')
 */
final class DropdownTextInput implements DropdownItem
{
    private string $label = '';

    private string $placeholder = '';

    /** HTML input type: 'text' | 'number' | 'email' | 'date' */
    private string $inputType = 'text';

    private string $defaultValue = '';

    private bool $disabled = false;

    private ?string $permission = null;

    private string $persist = 'none';

    private int $debounceMs = 400;

    private ?string $changeEvent = null;

    /** @var array<string, mixed> */
    private array $changePayload = [];

    private function __construct(private readonly string $itemKey) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    // ── Chainable setters ─────────────────────────────────────────────────────

    public function label(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function placeholder(string $placeholder): self
    {
        $clone = clone $this;
        $clone->placeholder = $placeholder;

        return $clone;
    }

    public function type(string $type): self
    {
        $clone = clone $this;
        $clone->inputType = $type;

        return $clone;
    }

    public function default(string $value): self
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
     * Debounce delay in milliseconds before the Livewire call fires.
     * Minimum effective value is 300 ms.
     */
    public function debounce(int $ms): self
    {
        $clone = clone $this;
        $clone->debounceMs = max(300, $ms);

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
        return 'text-input';
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getInputType(): string
    {
        return $this->inputType;
    }

    public function getDefault(): string
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

    public function getDebounceMs(): int
    {
        return $this->debounceMs;
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
