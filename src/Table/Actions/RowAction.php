<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Concerns\HasOpenInTab;

/**
 * Custom row action definition for TableBuilder.
 *
 * Row actions appear in the Actions column alongside edit/archive.
 * Each action can define its own icon, permission requirement, and
 * handler callback.
 *
 * Example:
 *   RowAction::make('clone')
 *       ->label('Clone Record')
 *       ->icon('copy')
 *       ->permission('activity_committees.create')
 *       ->confirm('Clone this record?')
 *       ->visible(fn($row) => !$row['archived'])
 */
final class RowAction
{
    use HasOpenInTab;

    private string $key;

    private string $label;

    private ?string $icon = null;

    private ?string $permission = null;

    private ?string $confirm = null;

    private mixed $visible = null;

    private string $color = 'primary';

    private bool $newWindow = false;

    /**
     * URL to navigate to when clicked. Supports `{id}` placeholder.
     * When set the action renders as an anchor tag instead of a button.
     */
    private ?string $url = null;

    /**
     * When set, clicking this action opens the TableBuilder panel in
     * 'custom' mode, loading the given Blade partial as the panel body.
     * The row data is passed to the partial as $data.
     */
    private ?string $panelBlade = null;

    private ?string $panelTitle = null;

    /**
     * Optional animation preset for the action button.
     * Supported: 'loading', 'spin', 'pulse'
     */
    private ?string $animation = null;

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
     * Permission node required to see this action. If null, inherits
     * the table's modify permission.
     */
    public function permission(?string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    /**
     * Confirmation prompt shown before executing the action.
     */
    public function confirm(string $message): self
    {
        $clone = clone $this;
        $clone->confirm = $message;

        return $clone;
    }

    /**
     * Callback to determine if this action should be visible for a
     * given row. Receives the row array as argument.
     *
     * @param  callable(array<string, mixed>): bool  $callback
     */
    public function visible(callable $callback): self
    {
        $clone = clone $this;
        $clone->visible = $callback;

        return $clone;
    }

    /**
     * Button color variant (primary, secondary, success, danger, etc.)
     */
    public function color(string $color): self
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    /**
     * Open action result in new window/tab.
     */
    public function newWindow(bool $newWindow = true): self
    {
        $clone = clone $this;
        $clone->newWindow = $newWindow;

        return $clone;
    }

    /**
     * Navigate to a URL when clicked. Supports `{id}` placeholder which
     * is replaced with the row's primary key at render time.
     *
     * When a URL is set the engine renders this action as an anchor tag
     * rather than a Livewire button, so no round-trip is required.
     *
     * Example: ->url('/activities/committees/{id}')
     */
    public function url(string $url): self
    {
        $clone = clone $this;
        $clone->url = $url;

        return $clone;
    }

    /**
     * Open a custom Blade partial inside the TableBuilder panel when clicked.
     *
     * The partial receives the row data as $data. The panel chrome
     * (title bar, close button) is owned by the panel; the partial
     * only needs to render its own body content.
     *
     * Custom partials can close the panel by dispatching the
     * 'architect:close-panel' Livewire event, and trigger a table
     * refresh with 'architect:refresh'.
     *
     * Example:
     *   RowAction::make('reassign')
     *       ->label('Reassign')
     *       ->panelView('activities.committees.reassign-panel', title: 'Reassign Member')
     */
    public function panelView(string $blade, string $title): self
    {
        $clone = clone $this;
        $clone->panelBlade = $blade;
        $clone->panelTitle = $title;

        return $clone;
    }

    /**
     * Animate the action button.
     *
     * - 'loading': While the Livewire round-trip is in-flight, the button is
     *              disabled and its content replaced with a spinner. Powered
     *              by wire:loading — only applies to Livewire-handled actions.
     * - 'spin':    The icon rotates continuously (e.g. refresh / sync).
     * - 'pulse':   The button pulses continuously (e.g. live indicator).
     */
    public function animation(string $type): self
    {
        $clone = clone $this;
        $clone->animation = $type;

        return $clone;
    }

    public function getPanelBlade(): ?string
    {
        return $this->panelBlade;
    }

    public function getPanelTitle(): ?string
    {
        return $this->panelTitle;
    }

    public function getAnimation(): ?string
    {
        return $this->animation;
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

    public function getConfirm(): ?string
    {
        return $this->confirm;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function opensInNewWindow(): bool
    {
        return $this->newWindow;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Check if this action should be visible for the given row.
     *
     * @param  array<string, mixed>  $row
     */
    public function isVisibleFor(array $row): bool
    {
        if ($this->visible === null) {
            return true;
        }

        return (bool) ($this->visible)($row);
    }
}
