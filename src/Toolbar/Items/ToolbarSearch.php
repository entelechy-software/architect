<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items;

use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;

/**
 * A search input that sits directly in the toolbar bar (not inside a dropdown).
 *
 * Two modes:
 *
 *   Simple (default) — debounced input calls ToolbarEngine::setSearch() and
 *   dispatches architect:toolbar:search-changed to the browser. The consuming
 *   Livewire component listens for that event and filters its data.
 *
 *   Suggest — debounced input calls ToolbarEngine::requestSuggestions(), which
 *   dispatches architect:toolbar:search-suggest-requested. A parent Livewire
 *   component handles that event, computes results, and responds by dispatching
 *   architect:toolbar:search-suggestions back. ToolbarEngine's #[On] handler
 *   populates $searchSuggestions and re-renders the flyout.
 *
 * Example (simple):
 *   ToolbarSearch::make('q')
 *       ->placeholder('Search cases…')
 *       ->persist('local')
 *       ->dispatchOnChange('cases:search-changed')
 *       ->clearable()
 *
 * Example (suggest):
 *   ToolbarSearch::make('q')
 *       ->placeholder('Find member…')
 *       ->suggest()
 *       ->minChars(2)
 *       ->debounce(400)
 *       ->dispatchOnChange('cases:member-selected')
 */
final class ToolbarSearch implements ToolbarItem
{
    private string $label = 'Search';

    private ?string $placeholder = null;

    private string $icon = 'fas fa-magnifying-glass';

    private bool $clearable = true;

    private bool $disabled = false;

    private ?string $permission = null;

    /** 'none' | 'local' | 'url' */
    private string $persist = 'none';

    private ?string $changeEvent = null;

    /** @var array<string, mixed> */
    private array $changePayload = [];

    /** Milliseconds before Livewire is called after the last keystroke. Minimum 200 ms. */
    private int $debounceMs = 350;

    /** Minimum query length before requestSuggestions fires. */
    private int $minChars = 1;

    private bool $suggestMode = false;

    private string $width = 'w-48';

    private string $pos = 'left';

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

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    public function noIcon(): self
    {
        $clone = clone $this;
        $clone->icon = '';

        return $clone;
    }

    public function clearable(bool $clearable = true): self
    {
        $clone = clone $this;
        $clone->clearable = $clearable;

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

    /**
     * Debounce delay in milliseconds. Clamped to a minimum of 200 ms.
     */
    public function debounce(int $ms): self
    {
        $clone = clone $this;
        $clone->debounceMs = max(200, $ms);

        return $clone;
    }

    /**
     * Minimum number of characters required before requestSuggestions fires.
     * Only relevant in suggest mode.
     */
    public function minChars(int $chars): self
    {
        $clone = clone $this;
        $clone->minChars = max(1, $chars);

        return $clone;
    }

    /**
     * Enable suggest (typeahead) mode.
     *
     * In this mode the toolbar dispatches architect:toolbar:search-suggest-requested
     * when the user types. A parent Livewire component should handle that event,
     * compute suggestions, and respond with architect:toolbar:search-suggestions.
     */
    public function suggest(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->suggestMode = $enabled;

        return $clone;
    }

    /**
     * Tailwind width class applied to the search input wrapper.
     * Defaults to 'w-48'. Use 'w-full' to expand to available space.
     */
    public function width(string $width): self
    {
        $clone = clone $this;
        $clone->width = $width;

        return $clone;
    }

    public function position(string $position): self
    {
        $clone = clone $this;
        $clone->pos = $position;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'search';
    }

    public function getPosition(): string
    {
        return $this->pos;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function isClearable(): bool
    {
        return $this->clearable;
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

    public function getDebounceMs(): int
    {
        return $this->debounceMs;
    }

    public function getMinChars(): int
    {
        return $this->minChars;
    }

    public function isSuggestMode(): bool
    {
        return $this->suggestMode;
    }

    public function getWidth(): string
    {
        return $this->width;
    }
}
