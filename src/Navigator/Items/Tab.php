<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Items;

use Entelechy\Architect\Concerns\HasOpenInTab;
use Entelechy\Architect\Navigator\Behaviours\LinkBehaviour;
use Entelechy\Architect\Navigator\Contracts\NavigatorItem;
use Illuminate\Support\Str;

/**
 * A single tab / pill item inside a NavigatorBuilder.
 *
 * Link-mode (existing behaviour):
 *   Tab::make('Elections')
 *       ->icon('fas fa-vote-yea')
 *       ->href('/elections')
 *       ->default()
 *
 * SPA-mode (when the navigator uses ->spa()):
 *   Tab::make('Management')
 *       ->architect(ActivitiesTableDefinition::class)
 *
 *   Tab::make('Stats')
 *       ->component(\App\Modules\Activities\Livewire\StatsPanel::class)
 *
 *   Tab::make('Help')
 *       ->view('activities.partials.help', ['section' => 'committees'])
 */
final class Tab implements NavigatorItem
{
    use HasOpenInTab;

    private ?string $icon = null;

    private ?string $href = null;

    private ?LinkBehaviour $behaviour = null;

    private bool $isDefault = false;

    private bool $disabled = false;

    /** @var list<string> Additional URL prefixes that activate this tab. */
    private array $activeOn = [];

    private ?int $badge = null;

    // ── SPA content ──────────────────────────────────────────────────────

    private ?string $explicitSlug = null;

    /** @var 'architect'|'component'|'view'|null */
    private ?string $contentType = null;

    private ?string $architectClass = null;

    private ?string $componentClass = null;

    /** @var array<string, mixed> */
    private array $componentProps = [];

    private ?string $viewPath = null;

    /** @var array<string, mixed> */
    private array $viewData = [];

    private function __construct(
        private readonly string $label,
    ) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    /**
     * Font Awesome or other icon class.
     * e.g. 'fas fa-vote-yea'
     */
    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    /**
     * Shorthand for Behaviour::link($url).
     *
     * Sets both href (for URL-matching) and the underlying behaviour.
     */
    public function href(string $url): self
    {
        $clone = clone $this;
        $clone->href = $url;
        $clone->behaviour = new LinkBehaviour($url);

        return $clone;
    }

    /**
     * Explicitly set the behaviour (LinkBehaviour or Phase B alternatives).
     *
     * When a LinkBehaviour is provided, its URL is also used for active-
     * state matching unless ->activeOn() overrides it.
     */
    public function behaviour(LinkBehaviour $behaviour): self
    {
        $clone = clone $this;
        $clone->behaviour = $behaviour;
        if ($clone->href === null) {
            $clone->href = $behaviour->url;
        }

        return $clone;
    }

    /**
     * Mark as the default/fallback tab when no URL prefix matches.
     */
    public function default(): self
    {
        $clone = clone $this;
        $clone->isDefault = true;

        return $clone;
    }

    /**
     * Disable interaction; renders the tab greyed-out and non-clickable.
     */
    public function disabled(): self
    {
        $clone = clone $this;
        $clone->disabled = true;

        return $clone;
    }

    /**
     * Additional URL prefixes that should mark this tab as active.
     *
     * Useful when a tab covers several related routes.
     *
     * @param  list<string>  $patterns
     */
    public function activeOn(array $patterns): self
    {
        $clone = clone $this;
        $clone->activeOn = $patterns;

        return $clone;
    }

    /**
     * Show a numeric badge pill on the tab.
     * A count of 0 shows "0"; pass null to remove.
     */
    public function badge(int $count): self
    {
        $clone = clone $this;
        $clone->badge = $count;

        return $clone;
    }

    // ── SPA content ──────────────────────────────────────────────────────

    /**
     * Embed an Architect table Engine as this tab's content.
     *
     * The Engine is mounted with embedded=true so URL-state sync and the
     * child navigator bar are suppressed.
     *
     * @param  class-string  $definitionClass
     */
    public function architect(string $definitionClass): self
    {
        $clone = clone $this;
        $clone->contentType = 'architect';
        $clone->architectClass = $definitionClass;

        return $clone;
    }

    /**
     * Mount a Livewire component as this tab's content.
     *
     * @param  class-string  $class  Fully-qualified Livewire component class.
     * @param  array<string, mixed>  $props  Mount properties passed to the component.
     */
    public function component(string $class, array $props = []): self
    {
        $clone = clone $this;
        $clone->contentType = 'component';
        $clone->componentClass = $class;
        $clone->componentProps = $props;

        return $clone;
    }

    /**
     * Render a Blade view as this tab's content.
     *
     * @param  string  $blade  View name as passed to @include (dot notation).
     * @param  array<string, mixed>  $data  Variables passed to the view.
     */
    public function view(string $blade, array $data = []): self
    {
        $clone = clone $this;
        $clone->contentType = 'view';
        $clone->viewPath = $blade;
        $clone->viewData = $data;

        return $clone;
    }

    /**
     * Explicit URL slug override for SPA mode (used as the ?tab= query value).
     *
     * When not set, the slug is auto-derived from the tab label via Str::slug().
     * Must be unique within the navigator.
     */
    public function withSlug(string $slug): self
    {
        $clone = clone $this;
        $clone->explicitSlug = $slug;

        return $clone;
    }

    public function getItemType(): string
    {
        return 'tab';
    }

    // ── Accessors ────────────────────────────────────────────────────────

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getBehaviour(): ?LinkBehaviour
    {
        return $this->behaviour;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Determine whether this tab should render as active for the given URL path.
     *
     * Performs a prefix match on the href and any additional activeOn patterns.
     */
    public function isActiveForPath(string $path): bool
    {
        if ($this->href !== null) {
            $normalized = rtrim($this->href, '/');
            if ($normalized !== '' && str_starts_with(rtrim($path, '/'), $normalized)) {
                return true;
            }
            if ($normalized === '' && $path === '/') {
                return true;
            }
        }

        foreach ($this->activeOn as $pattern) {
            $normalized = rtrim($pattern, '/');
            if ($normalized !== '' && str_starts_with(rtrim($path, '/'), $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function getActiveOnPatterns(): array
    {
        return $this->activeOn;
    }

    public function getBadge(): ?int
    {
        return $this->badge;
    }

    // ── SPA accessors ────────────────────────────────────────────────────

    /**
     * URL slug for SPA mode query param.
     * Auto-derived from the label when not explicitly set.
     */
    public function getSlug(): string
    {
        return $this->explicitSlug ?? Str::slug($this->label);
    }

    public function hasContent(): bool
    {
        return $this->contentType !== null;
    }

    /** @return 'architect'|'component'|'view'|null */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getArchitectClass(): ?string
    {
        return $this->architectClass;
    }

    public function getComponentClass(): ?string
    {
        return $this->componentClass;
    }

    /** @return array<string, mixed> */
    public function getComponentProps(): array
    {
        return $this->componentProps;
    }

    public function getViewPath(): ?string
    {
        return $this->viewPath;
    }

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        return $this->viewData;
    }
}
