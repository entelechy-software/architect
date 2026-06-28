<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Dropdown;

use Entelechy\Architect\Concerns\HasOpenInTab;
use Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem;

/**
 * A standard clickable action item inside a ToolbarDropdown.
 *
 * Supports URL navigation, Livewire method call, or browser event dispatch.
 *
 * Example:
 *   DropdownAction::make('edit')
 *       ->label('Edit')
 *       ->icon('fas fa-pencil')
 *       ->href('/advice/cases/{id}/edit')
 *       ->permission('advice_cases.modify')
 */
final class DropdownAction implements DropdownItem
{
    use HasOpenInTab;

    private string $label = '';

    private ?string $icon = null;

    private bool $disabled = false;

    private ?string $permission = null;

    private ?string $href = null;

    private ?string $wireClick = null;

    private ?string $dispatchEvent = null;

    /** @var array<string, mixed> */
    private array $dispatchPayload = [];

    private bool $newWindow = false;

    private ?string $confirm = null;

    private string $color = 'default';

    private ?string $badge = null;

    private string $badgeColor = 'secondary';

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

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

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

    public function href(string $url): self
    {
        $clone = clone $this;
        $clone->href = $url;

        return $clone;
    }

    public function wireClick(string $expression): self
    {
        $clone = clone $this;
        $clone->wireClick = $expression;

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $event, array $payload = []): self
    {
        $clone = clone $this;
        $clone->dispatchEvent = $event;
        $clone->dispatchPayload = $payload;

        return $clone;
    }

    public function newWindow(bool $newWindow = true): self
    {
        $clone = clone $this;
        $clone->newWindow = $newWindow;

        return $clone;
    }

    /**
     * Show a browser confirm() dialog before executing the action.
     */
    public function confirm(string $message): self
    {
        $clone = clone $this;
        $clone->confirm = $message;

        return $clone;
    }

    public function color(string $color): self
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    public function badge(string $text, string $color = 'secondary'): self
    {
        $clone = clone $this;
        $clone->badge = $text;
        $clone->badgeColor = $color;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'action';
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getWireClick(): ?string
    {
        return $this->wireClick;
    }

    public function getDispatchEvent(): ?string
    {
        return $this->dispatchEvent;
    }

    /** @return array<string, mixed> */
    public function getDispatchPayload(): array
    {
        return $this->dispatchPayload;
    }

    public function isNewWindow(): bool
    {
        return $this->newWindow;
    }

    public function getConfirm(): ?string
    {
        return $this->confirm;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getBadgeColor(): string
    {
        return $this->badgeColor;
    }
}
