<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator;

use Entelechy\Architect\Navigator\Contracts\NavigatorItem;
use Entelechy\Architect\Navigator\Items\DynamicTabType;
use Entelechy\Architect\Navigator\Items\NavButton;
use Entelechy\Architect\Navigator\Items\NavSeparator;
use Entelechy\Architect\Navigator\Items\PinnedTab;
use Entelechy\Architect\Navigator\Items\StepItem;
use Entelechy\Architect\Navigator\Items\Tab;

/**
 * Fluent builder for ArchitectNavigatorDefinition.
 *
 * Usage (standalone):
 *   $nav = NavigatorBuilder::navigator()
 *       ->type('tabs')
 *       ->tab(Tab::make('Elections')->href('/elections')->default())
 *       ->tab(Tab::make('Periods')->href('/elections/periods'))
 *       ->build();
 *
 * Usage (inside a table definition):
 *   Architect::make('table')
 *       ->navigator(
 *           Architect::make('navigator')
 *               ->type('tabs')
 *               ->tab(Tab::make('Elections')->href('/elections')->default())
 *               ->build()
 *       )
 *       ->...
 *
 * Available types: tabs | pills | buttons | toolbar | stepper | sidebar | dropdown | workspace-tabs
 *
 * Supported positions: top (default), bottom, left, right.
 * Supported alignments: start (default), center, end, fill.
 */
final class NavigatorBuilder
{
    /** @var list<NavigatorItem> */
    private array $items = [];

    private string $position = 'top';

    private string $align = 'start';

    private bool $validateOnStep = false;

    private ?string $validateWithForm = null;

    private ?string $validateWithMethod = null;

    // ── Nav-tabs style (only used when type = 'tabs') ─────────────────────
    private string $tabStyle = 'button';

    // ── SPA mode (tabs / pills / buttons / sidebar only) ─────────────────
    private bool $spa = false;

    private string $loadingStrategy = 'eager';

    private ?string $urlParam = 'tab';

    // ── Breadcrumbs ───────────────────────────────────────────────────────
    /** @var array<int, array{title: string, url?: string}> */
    private array $breadcrumbs = [];

    private bool $inheritBreadcrumbs = false;

    private ?string $permission = null;

    // ── Workspace-tabs state (only used when type = 'workspace-tabs') ─────
    private string $workspaceKey = '';

    private bool $workspacePersist = false;

    private int $workspaceMaxTabs = 10;

    /** @var list<PinnedTab> */
    private array $workspacePinnedTabs = [];

    /** @var array<string, DynamicTabType> */
    private array $workspaceDynamicTypes = [];

    private bool $workspaceShowOverflowPopover = true;

    private bool $workspaceShowRecentlyClosed = true;

    private int $workspaceRecentlyClosedMax = 5;

    private bool $workspaceEnableSwitcherPalette = true;

    private bool $workspaceNotifyStaleRecords = false;

    private string $type = '';

    private function __construct() {}

    // ── Named constructor ─────────────────────────────────────────────────

    /**
     * Begin building a navigator. Call ->type() immediately after.
     *
     * Usage:
     *   NavigatorBuilder::make()->type('tabs')->style('page')->tab(...)->build()
     *   NavigatorBuilder::make()->type('workspace-tabs')->workspaceKey('advice-center')->...->build()
     */
    public static function make(): self
    {
        return new self;
    }

    // ── Type ──────────────────────────────────────────────────────────────

    /**
     * Set the navigator type.
     *
     * Available types:
     *   tabs          — Horizontal tab bar (pill-button or page-underline via ->style())
     *   pills         — Bootstrap nav-pills filled pill style
     *   buttons       — Horizontal button-group row
     *   toolbar       — Compact icon+label toolbar
     *   stepper       — Vertical numbered wizard stepper
     *   sidebar       — Vertical left-rail sidebar
     *   dropdown      — Collapsible single-trigger dropdown menu
     *   workspace-tabs — Full-width Livewire workspace (call ->workspaceKey() after)
     */
    public function type(string $type): self
    {
        $clone = clone $this;
        $valid = ['tabs', 'pills', 'buttons', 'toolbar', 'stepper', 'sidebar', 'dropdown', 'workspace-tabs'];
        if (! in_array($type, $valid, true)) {
            throw new \InvalidArgumentException(
                'NavigatorBuilder::type() must be one of: '.implode(', ', $valid).". Got '{$type}'"
            );
        }

        $clone->type = $type;

        return $clone;
    }

    // ── Workspace key (workspace-tabs only) ───────────────────────────────

    /**
     * Unique slug for this workspace — used for localStorage persistence.
     *
     * Required when type is 'workspace-tabs'. E.g. ->workspaceKey('advice-center').
     */
    public function workspaceKey(string $key): self
    {
        $clone = clone $this;
        $clone->workspaceKey = $key;

        return $clone;
    }

    // ── Chainable builder methods ─────────────────────────────────────────

    /**
     * Set the position of the navigator relative to the content block.
     *
     * KNOWN GAP (tracked, not yet wired): this value is validated and stored
     * on ArchitectNavigatorDefinition::$position, but no Blade partial or
     * component reads it anywhere in the package — Navigator renders as a
     * standalone component (never wraps host content), so applying
     * top/bottom/left/right automatically isn't possible without the host
     * app's own layout markup. Today this is purely descriptive metadata:
     * place the `<x-architect::static>`/navigator tag in your own template
     * wherever you intend "top/bottom/left/right" to mean. See
     * ARCHITECT_IMPROVEMENT_PLAN.md Phase 2 Feature Matrix.
     *
     * @param  string  $position  top | bottom | left | right
     */
    public function position(string $position): self
    {
        $clone = clone $this;
        $valid = ['top', 'bottom', 'left', 'right'];
        if (! in_array($position, $valid, true)) {
            throw new \InvalidArgumentException(
                'NavigatorBuilder position must be one of: '.implode(', ', $valid).". Got '{$position}'"
            );
        }

        $clone->position = $position;

        return $clone;
    }

    /**
     * Set the alignment of the item list within the navigator row.
     *
     * @param  string  $align  start | center | end | fill
     */
    public function align(string $align): self
    {
        $clone = clone $this;
        $valid = ['start', 'center', 'end', 'fill'];
        if (! in_array($align, $valid, true)) {
            throw new \InvalidArgumentException(
                'NavigatorBuilder align must be one of: '.implode(', ', $valid).". Got '{$align}'"
            );
        }

        $clone->align = $align;

        return $clone;
    }

    /**
     * Add a Tab item. Recommended for tabs() and pills() navigators.
     */
    public function tab(Tab $tab): self
    {
        $this->items[] = $tab;

        return $this;
    }

    /**
     * Add a NavButton item. Recommended for buttons() and toolbar() navigators.
     */
    public function button(NavButton $button): self
    {
        $this->items[] = $button;

        return $this;
    }

    /**
     * Add a StepItem. Recommended for stepper() navigators.
     */
    public function step(StepItem $step): self
    {
        $this->items[] = $step;

        return $this;
    }

    /**
     * Add a visual separator between items.
     */
    public function separator(): self
    {
        $this->items[] = NavSeparator::make();

        return $this;
    }

    /**
     * Add any NavigatorItem (escape hatch for custom items).
     */
    public function item(NavigatorItem $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Set the display style for nav-tabs type.
     *
     * @param  string  $style  'button' (default pill-button row) | 'page' (underline border-bottom row)
     */
    public function style(string $style): self
    {
        $clone = clone $this;
        $valid = ['button', 'page'];
        if (! in_array($style, $valid, true)) {
            throw new \InvalidArgumentException(
                'NavigatorBuilder::style() must be one of: '.implode(', ', $valid).". Got '{$style}'"
            );
        }

        $clone->tabStyle = $style;

        return $clone;
    }

    // ── SPA methods ───────────────────────────────────────────────────────

    /**
     * Enable SPA (single-page) mode.
     *
     * In SPA mode, tab content is embedded inline on the same page and
     * switching is handled client-side by Alpine. No page navigation occurs.
     *
     * Each Tab must declare its content via ->architect(), ->component(),
     * or ->view().
     */
    public function spa(): self
    {
        $clone = clone $this;
        $clone->spa = true;

        return $clone;
    }

    /**
     * Load all SPA tab panels into the initial HTML (default).
     *
     * All Livewire components mount on page load; Alpine hides non-active
     * panels with x-show. Best for lightweight tabs or when deep-linking
     * requires immediate availability.
     */
    public function eager(): self
    {
        $clone = clone $this;
        $clone->loadingStrategy = 'eager';

        return $clone;
    }

    /**
     * Defer each SPA panel until its tab is first activated.
     *
     * Livewire components use lazy: 'on-load', which fires immediately after
     * the initial page load via a follow-up AJAX request. A placeholder
     * skeleton is shown until the component hydrates.
     *
     * Recommended for pages with several heavyweight table panels.
     */
    public function lazy(): self
    {
        $clone = clone $this;
        $clone->loadingStrategy = 'lazy';

        return $clone;
    }

    /**
     * Set the query-string parameter name used for SPA tab deep-linking.
     *
     * Defaults to 'tab' (e.g. ?tab=management). Pass null to disable URL
     * sync entirely (tab state is not preserved on refresh or bookmark).
     */
    public function urlParam(?string $param = 'tab'): self
    {
        $clone = clone $this;
        $clone->urlParam = $param;

        return $clone;
    }

    // ── Breadcrumbs ───────────────────────────────────────────────────────

    /**
     * Set breadcrumbs for this SPA navigator.
     *
     * Two forms:
     *   ->breadcrumbs([['title' => 'Advice'], ['title' => 'Options', 'url' => '/advice/options']])
     *       Defines a static breadcrumb trail shown in the topbar for the whole page.
     *
     *   ->breadcrumbs('inherit')
     *       Resolves breadcrumbs from each tab's embedded ArchitectTableDefinition at runtime.
     *       The topbar updates when the active SPA tab changes. Only works for tabs using
     *       ->architect(); component/view tabs receive empty crumbs for their slot.
     *
     * "Defined" always wins: calling with an array clears any prior ->breadcrumbs('inherit').
     *
     * @param  array<int, array{title: string, url?: string}>|string  $crumbs
     */
    public function breadcrumbs(array|string $crumbs): self
    {
        if (is_string($crumbs) && $crumbs !== 'inherit') {
            throw new \InvalidArgumentException("NavigatorBuilder::breadcrumbs() only accepts an array or the literal string 'inherit', got '{$crumbs}'.");
        }

        $clone = clone $this;
        if ($crumbs === 'inherit') {
            $clone->inheritBreadcrumbs = true;
            $clone->breadcrumbs = [];
        } else {
            $clone->breadcrumbs = $crumbs;
            $clone->inheritBreadcrumbs = false;
        }

        return $clone;
    }

    /**
     * Permission node gating the whole navigator. When set, SpaTabsEngine
     * checks it on every render and dispatches `architect:unauthorized`
     * if the current user lacks it.
     */
    public function permission(?string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    // ── Workspace builder methods (effective for workspaceTabs() only) ────

    /**
     * Enable localStorage persistence of open dynamic tabs across page loads.
     */
    public function persist(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->workspacePersist = $enabled;

        return $clone;
    }

    /**
     * Maximum number of simultaneously open dynamic tabs (LRU eviction).
     */
    public function maxTabs(int $max): self
    {
        $clone = clone $this;
        $clone->workspaceMaxTabs = $max;

        return $clone;
    }

    /**
     * Add a pinned tab (always open, cannot be closed).
     */
    public function pinnedTab(PinnedTab $tab): self
    {
        $this->workspacePinnedTabs[] = $tab;

        return $this;
    }

    /**
     * Register a dynamic tab type (openable at runtime via architect:open-record).
     */
    public function dynamicTab(DynamicTabType $type): self
    {
        $this->workspaceDynamicTypes[$type->getType()] = $type;

        return $this;
    }

    /**
     * Show a "+N more" overflow popover when tabs exceed the bar width.
     */
    public function showOverflowPopover(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->workspaceShowOverflowPopover = $enabled;

        return $clone;
    }

    /**
     * Show a ↩ button to reopen the most recently closed dynamic tab.
     *
     * @param  int  $max  How many recently-closed tabs to remember (default 5, 0 to disable).
     */
    public function showRecentlyClosed(int $max = 5): self
    {
        $clone = clone $this;
        $clone->workspaceShowRecentlyClosed = $max > 0;
        $clone->workspaceRecentlyClosedMax = max(0, $max);

        return $clone;
    }

    /**
     * Enable the Ctrl+Shift+T tab-switcher command palette.
     */
    public function enableSwitcherPalette(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->workspaceEnableSwitcherPalette = $enabled;

        return $clone;
    }

    /**
     * When a record is saved in one tab, other open tabs for related records
     * receive a ⟳ stale-data indicator.
     */
    public function notifyStaleRecords(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->workspaceNotifyStaleRecords = $enabled;

        return $clone;
    }

    /**
     * Enable step-locking for stepper navigators.
     *
     * When enabled, steps beyond the first incomplete step are locked
     * (rendered as non-clickable spans). Completed steps and all steps
     * up to the first incomplete one remain clickable so users can go
     * back and amend prior answers.
     *
     * Steps are marked complete via StepItem::completed(bool).
     */
    public function validateOnStep(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->validateOnStep = $enabled;

        return $clone;
    }

    /**
     * Guard forward-step navigation with HTML5 form validity.
     *
     * When set, clicking a future step calls checkValidity() on the form
     * element with the given ID. If the form is invalid, reportValidity()
     * is called (shows native browser validation bubbles) and navigation
     * is cancelled. Backward navigation (to completed/prior steps) is
     * never blocked.
     *
     * Requires the stepper to be rendered inside or alongside the form.
     * Uses the <code>stepperGuard</code> Alpine component.
     *
     * @param  string|null  $formId  HTML id attribute of the &lt;form&gt; element.
     */
    public function validateWithForm(?string $formId): self
    {
        $clone = clone $this;
        $clone->validateWithForm = $formId;

        return $clone;
    }

    /**
     * Guard forward-step navigation with a Livewire component method.
     *
     * When set, clicking a future step calls await $wire->methodName() and
     * awaits the result. The method must return bool — false cancels
     * navigation. The Livewire component is responsible for displaying
     * any validation errors (toast, inline message, etc.).
     *
     * Backward navigation is never blocked. If the stepper is not inside
     * a Livewire component, the wire check is skipped and navigation
     * proceeds normally.
     *
     * Example Livewire method:
     *   public function validateCurrentStep(): bool
     *   {
     *       try {
     *           $this->validate(['name' => 'required']);
     *           return true;
     *       } catch (\Illuminate\Validation\ValidationException) {
     *           return false;
     *       }
     *   }
     *
     * Uses the <code>stepperGuard</code> Alpine component.
     *
     * @param  string|null  $method  Public method name on the Livewire component.
     */
    public function validateWithMethod(?string $method): self
    {
        $clone = clone $this;
        $clone->validateWithMethod = $method;

        return $clone;
    }

    /**
     * Produce the immutable definition.
     *
     * @throws \LogicException If the definition is incomplete.
     */
    public function build(): ArchitectNavigatorDefinition
    {
        if ($this->type === '') {
            throw new \LogicException(
                'NavigatorBuilder: call ->type() before ->build(). '.
                'Valid types: tabs, pills, buttons, toolbar, stepper, sidebar, dropdown, workspace-tabs.'
            );
        }

        if ($this->type === 'workspace-tabs') {
            if ($this->workspacePinnedTabs === [] && $this->workspaceDynamicTypes === []) {
                throw new \LogicException(
                    'NavigatorBuilder::workspaceTabs() requires at least one pinnedTab() or dynamicTab() before build().'
                );
            }

            $workspaceDef = new WorkspaceTabsDefinition(
                key: $this->workspaceKey,
                persist: $this->workspacePersist,
                maxTabs: $this->workspaceMaxTabs,
                pinnedTabs: $this->workspacePinnedTabs,
                dynamicTypes: $this->workspaceDynamicTypes,
                showOverflowPopover: $this->workspaceShowOverflowPopover,
                showRecentlyClosed: $this->workspaceShowRecentlyClosed,
                recentlyClosedMax: $this->workspaceRecentlyClosedMax,
                enableSwitcherPalette: $this->workspaceEnableSwitcherPalette,
                notifyStaleRecords: $this->workspaceNotifyStaleRecords,
            );

            return new ArchitectNavigatorDefinition(
                type: 'workspace-tabs',
                position: $this->position,
                align: $this->align,
                items: [],
                workspaceDefinition: $workspaceDef,
                permission: $this->permission,
            );
        }

        if ($this->items === []) {
            throw new \LogicException('NavigatorBuilder: at least one item is required before calling build().');
        }

        if ($this->spa) {
            $slugs = [];
            foreach ($this->items as $item) {
                if ($item instanceof Tab) {
                    $slug = $item->getSlug();
                    if (in_array($slug, $slugs, true)) {
                        throw new \LogicException(
                            "NavigatorBuilder: duplicate SPA tab slug '{$slug}'. Use ->getSlug() to set a unique slug."
                        );
                    }
                    $slugs[] = $slug;
                }
            }
        }

        // Assign step numbers to StepItem instances in declaration order.
        $stepCounter = 1;
        $numberedItems = [];
        foreach ($this->items as $item) {
            $numberedItems[] = $item instanceof StepItem ? $item->withStep($stepCounter++) : $item;
        }

        return new ArchitectNavigatorDefinition(
            type: $this->type,
            position: $this->position,
            align: $this->align,
            items: $numberedItems,
            validateOnStep: $this->validateOnStep,
            validateWithForm: $this->validateWithForm,
            validateWithMethod: $this->validateWithMethod,
            tabStyle: $this->tabStyle,
            spa: $this->spa,
            loadingStrategy: $this->loadingStrategy,
            urlParam: $this->urlParam,
            breadcrumbs: $this->breadcrumbs,
            inheritBreadcrumbs: $this->inheritBreadcrumbs,
            permission: $this->permission,
        );
    }
}
