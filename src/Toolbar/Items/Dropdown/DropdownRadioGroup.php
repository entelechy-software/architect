<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Dropdown;

use Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem;

/**
 * A group of mutually-exclusive radio options rendered vertically inside
 * a ToolbarDropdown. Each click selects that option without closing the dropdown.
 *
 * State stored in ToolbarEngine::$radioValues under the compound key
 * "dropdownKey.radioGroupKey". Dispatches architect:toolbar:radio-changed
 * with that compound key — consistent with ToolbarRadioGroup.
 *
 * Example:
 *   DropdownRadioGroup::make('sort')
 *       ->option('name', 'Name')
 *       ->option('date', 'Date created')
 *       ->option('status', 'Status')
 *       ->default('date')
 *       ->persist('local')
 *       ->dispatchOnChange('cases:sort-changed')
 */
final class DropdownRadioGroup implements DropdownItem
{
    /** @var list<array{value: string, label: string, icon: string|null}> */
    private array $options = [];

    private ?string $defaultValue = null;

    private string $persist = 'none';

    private ?string $changeEvent = null;

    /** @var array<string, mixed> */
    private array $changePayload = [];

    private bool $disabled = false;

    private function __construct(private readonly string $itemKey) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    // ── Chainable setters ─────────────────────────────────────────────────────

    public function option(string $value, string $label, ?string $icon = null): self
    {
        $clone = clone $this;
        $clone->options[] = [
            'value' => $value,
            'label' => $label,
            'icon' => $icon,
        ];

        return $clone;
    }

    public function default(string $value): self
    {
        $clone = clone $this;
        $clone->defaultValue = $value;

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

    public function disabled(bool $disabled = true): self
    {
        $clone = clone $this;
        $clone->disabled = $disabled;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'radio-group';
    }

    /** @return list<array{value: string, label: string, icon: string|null}> */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getDefault(): ?string
    {
        return $this->defaultValue;
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

    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}
