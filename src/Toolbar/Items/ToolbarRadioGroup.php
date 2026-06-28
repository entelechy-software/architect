<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items;

use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;

/**
 * A mutually-exclusive segmented radio control — visually like the stats
 * granularity switcher but generalised and stateful.
 *
 * State is managed server-side in ToolbarEngine and persisted to localStorage
 * or URL params depending on ->persist().
 *
 * Dispatches architect:toolbar:radio-changed { toolbarKey, itemKey, value }
 * on every change.
 *
 * Example:
 *   ToolbarRadioGroup::make('view')
 *       ->option('list',  'List',  icon: 'fas fa-list')
 *       ->option('card',  'Cards', icon: 'fas fa-th')
 *       ->default('list')
 *       ->persist('local')
 *       ->dispatchOnChange('architect:toolbar:view-changed')
 *       ->position('left')
 */
final class ToolbarRadioGroup implements ToolbarItem
{
    /** @var list<array{value: string, label: string, icon: string|null, disabled: bool}> */
    private array $options = [];

    private ?string $defaultValue = null;

    private string $persist = 'none';

    private ?string $changeEvent = null;

    /** @var array<string, mixed> */
    private array $changePayload = [];

    private string $pos = 'left';

    private bool $disabled = false;

    private string $size = 'sm';

    private function __construct(private readonly string $itemKey) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    // ── Chainable setters ─────────────────────────────────────────────────────

    public function option(string $value, string $label, ?string $icon = null, bool $disabled = false): self
    {
        $clone = clone $this;
        $clone->options[] = [
            'value' => $value,
            'label' => $label,
            'icon' => $icon,
            'disabled' => $disabled,
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
     * @param  array<string, mixed>  $payload  Extra keys merged into the dispatched event payload.
     */
    public function dispatchOnChange(string $event, array $payload = []): self
    {
        $clone = clone $this;
        $clone->changeEvent = $event;
        $clone->changePayload = $payload;

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
        return 'radio-group';
    }

    public function getPosition(): string
    {
        return $this->pos;
    }

    /** @return list<array{value: string, label: string, icon: string|null, disabled: bool}> */
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

    public function getSize(): string
    {
        return $this->size;
    }
}
