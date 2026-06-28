<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Items;

use Entelechy\Architect\Table\Livewire\Engine;

/**
 * Represents a pinned tab in a workspace tabs definition.
 *
 * Pinned tabs are always open, cannot be closed by the user, and are
 * declared in PHP at definition time. They are always mounted on page
 * load (unless ->lazyMount() is set, in which case they are mounted
 * on first activation).
 *
 * Usage:
 *   PinnedTab::make('Cases')
 *       ->icon('fas fa-folder-open')
 *       ->component(\App\Modules\Advice\Livewire\CasesList::class)
 *       ->props(['status' => 'open'])
 *       ->lazyMount()
 */
final class PinnedTab
{
    private ?string $icon = null;

    /** @var array<string, mixed> */
    private array $props = [];

    private bool $lazyMount = false;

    /** @var list<PinnedTabDropdownItem> */
    private array $dropdownItems = [];

    private bool $noContent = false;

    private function __construct(
        private readonly string $label,
        private string $component,
    ) {}

    public static function make(string $label): self
    {
        return new self($label, '');
    }

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    /**
     * Livewire component FQCN or alias to mount in this tab.
     */
    public function component(string $component): self
    {
        $clone = clone $this;
        $clone->component = $component;

        return $clone;
    }

    /**
     * Embed an Architect table definition in this tab.
     *
     * Shorthand for ->component(Engine::class)->props(['definitionClass' => $fqcn]).
     * The FQCN must point to a class whose ::definition() returns an
     * ArchitectTableDefinition.
     */
    public function architect(string $definitionClass): self
    {
        $clone = clone $this;
        $clone->component = Engine::class;
        $clone->props = ['definitionClass' => $definitionClass];

        return $clone;
    }

    /**
     * Static props passed to the Livewire component on mount.
     *
     * @param  array<string, mixed>  $props
     */
    public function props(array $props): self
    {
        $clone = clone $this;
        $clone->props = $props;

        return $clone;
    }

    /**
     * Defer mounting until the tab is first activated.
     */
    public function lazyMount(): self
    {
        $clone = clone $this;
        $clone->lazyMount = true;

        return $clone;
    }

    /**
     * Add a dropdown menu item to this tab.
     *
     * When any item is selected, the tab label updates to that item's label
     * and the item's configured Livewire event is dispatched globally.
     * Requires at least one dropdown item to render the chevron toggle.
     */
    public function dropdownItem(PinnedTabDropdownItem $item): self
    {
        $this->dropdownItems[] = $item;

        return $this;
    }

    /**
     * Insert a visual divider line between dropdown items.
     */
    public function separator(): self
    {
        $this->dropdownItems[] = PinnedTabDropdownItem::makeSeparator();

        return $this;
    }

    /**
     * Mark this tab as having no Livewire content panel.
     *
     * The tab button is rendered in the bar but no component is mounted in
     * the content area. Use when the page already renders its own content
     * (e.g. a dashboard card grid) that should remain visible while this
     * tab is active.
     */
    public function noContent(): self
    {
        $clone = clone $this;
        $clone->noContent = true;

        return $clone;
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

    public function getComponent(): string
    {
        return $this->component;
    }

    /** @return array<string, mixed> */
    public function getProps(): array
    {
        return $this->props;
    }

    public function isLazyMount(): bool
    {
        return $this->lazyMount;
    }

    /**
     * Stable, deterministic ID for this pinned tab.
     */
    public function getId(): string
    {
        return 'pinned-'.preg_replace('/[^a-z0-9]+/', '-', strtolower($this->label));
    }

    /**
     * Serialise to the array shape stored in Livewire's $openTabs state.
     *
     * @return array<string, mixed>
     */
    public function toTabArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => 'pinned',
            'label' => $this->label,
            'icon' => $this->icon,
            'component' => $this->noContent ? '' : $this->component,
            'props' => $this->props,
            'pinned' => true,
            'lazy' => $this->lazyMount,
            'mounted' => ! $this->lazyMount && ! $this->noContent,
            'dropdown_items' => array_map(fn (PinnedTabDropdownItem $i) => $i->toArray(), $this->dropdownItems),
        ];
    }
}
