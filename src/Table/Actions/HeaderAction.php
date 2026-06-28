<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Concerns\HasOpenInTab;

/**
 * Custom header action definition for TableBuilder toolbar.
 *
 * Header actions appear in the card header alongside search/filters.
 * Common built-in actions: refresh, print, column visibility.
 *
 * Example:
 *   HeaderAction::make('import')
 *       ->label('Import')
 *       ->icon('upload')
 *       ->url('/activities/committees/import')
 *       ->permission('activity_committees.create')
 */
final class HeaderAction
{
    use HasOpenInTab;

    private string $key;

    private string $label;

    private ?string $icon = null;

    private ?string $permission = null;

    private ?string $url = null;

    private ?string $wireClick = null;

    private string $color = 'secondary';

    private bool $outline = true;

    private bool $newWindow = false;

    private function __construct(string $key)
    {
        $this->key = $key;
        $this->label = ucfirst(str_replace('_', ' ', $key));
    }

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

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    /**
     * Permission node required to see this action. If null, the action
     * is visible to anyone who can read the table.
     */
    public function permission(?string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    /**
     * Static URL to navigate to when clicked.
     */
    public function url(string $url): self
    {
        $clone = clone $this;
        $clone->url = $url;

        return $clone;
    }

    /**
     * Livewire action to dispatch when clicked (e.g., '$refresh').
     */
    public function wireClick(string $method): self
    {
        $clone = clone $this;
        $clone->wireClick = $method;

        return $clone;
    }

    /**
     * Button color variant (primary, secondary, success, etc.)
     */
    public function color(string $color): self
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    /**
     * Render as filled button instead of outline.
     */
    public function filled(bool $filled = true): self
    {
        $clone = clone $this;
        $clone->outline = ! $filled;

        return $clone;
    }

    /**
     * Open URL in new window/tab.
     */
    public function newWindow(bool $newWindow = true): self
    {
        $clone = clone $this;
        $clone->newWindow = $newWindow;

        return $clone;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getWireClick(): ?string
    {
        return $this->wireClick;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function isOutline(): bool
    {
        return $this->outline;
    }

    public function isFilled(): bool
    {
        return ! $this->outline;
    }

    public function opensInNewWindow(): bool
    {
        return $this->newWindow;
    }

    public function isNewWindow(): bool
    {
        return $this->newWindow;
    }
}
