<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items;

use Entelechy\Architect\Concerns\HasOpenInTab;
use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;

/**
 * A single action button in the toolbar.
 *
 * Supports three mutually exclusive action modes:
 *   - URL navigation  → set via ->href()
 *   - Livewire method → set via ->wireClick()
 *   - Browser event   → set via ->dispatch()
 *   - Panel view      → set via ->panelView()
 *
 * Example:
 *   ToolbarButton::make('create')
 *       ->label('New Case')
 *       ->icon('fas fa-plus')
 *       ->color('primary')
 *       ->wireClick('$dispatch("architect:open-create", {})')
 *       ->permission('advice_cases.create')
 *       ->position('left')
 */
final class ToolbarButton implements ToolbarItem
{
    use HasOpenInTab;

    private string $label = '';

    private ?string $icon = null;

    private string $color = 'secondary';

    private bool $outlined = true;

    private bool $disabled = false;

    private ?string $permission = null;

    private ?string $href = null;

    private ?string $wireClick = null;

    private ?string $dispatchEvent = null;

    /** @var array<string, mixed> */
    private array $dispatchPayload = [];

    private ?string $panelBlade = null;

    private ?string $panelTitle = null;

    private bool $newWindow = false;

    private ?string $badge = null;

    private string $badgeColor = 'secondary';

    private string $pos = 'left';

    private ?string $tooltip = null;

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

    public function color(string $color): self
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    public function filled(bool $filled = true): self
    {
        $clone = clone $this;
        $clone->outlined = ! $filled;

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

    public function panelView(string $blade, string $title = ''): self
    {
        $clone = clone $this;
        $clone->panelBlade = $blade;
        $clone->panelTitle = $title;

        return $clone;
    }

    public function newWindow(bool $newWindow = true): self
    {
        $clone = clone $this;
        $clone->newWindow = $newWindow;

        return $clone;
    }

    public function badge(string $text, string $color = 'secondary'): self
    {
        $clone = clone $this;
        $clone->badge = $text;
        $clone->badgeColor = $color;

        return $clone;
    }

    public function position(string $position): self
    {
        $clone = clone $this;
        $clone->pos = $position;

        return $clone;
    }

    public function tooltip(string $text): self
    {
        $clone = clone $this;
        $clone->tooltip = $text;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'button';
    }

    public function getPosition(): string
    {
        return $this->pos;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function isOutlined(): bool
    {
        return $this->outlined;
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

    public function getPanelBlade(): ?string
    {
        return $this->panelBlade;
    }

    public function getPanelTitle(): ?string
    {
        return $this->panelTitle;
    }

    public function isNewWindow(): bool
    {
        return $this->newWindow;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getBadgeColor(): string
    {
        return $this->badgeColor;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }
}
