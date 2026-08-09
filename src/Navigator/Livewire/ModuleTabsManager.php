<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Livewire;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Navigator\ArchitectNavigatorDefinition;
use Entelechy\Architect\Navigator\Contracts\ProvidesNavigatorDefinition;
use Entelechy\Architect\Navigator\WorkspaceTabsDefinition;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use LogicException;

/**
 * ModuleTabsManager — Livewire host for a full-width in-page tab workspace.
 *
 * Responsibilities:
 *  - Holds the list of currently open tabs ($openTabs) as Livewire state.
 *  - Handles openTab / closeTab / restoreTabs mutations (triggered by
 *    Alpine via $wire calls or by the architect:open-record global event).
 *  - Renders all tab content panels simultaneously; Alpine's x-show
 *    handles visibility switching with zero server round-trips.
 *
 * Each tab in $openTabs is an array with shape:
 *   {
 *     id:        string   — stable, deterministic tab ID
 *     type:      string   — 'pinned' | registered DynamicTabType key
 *     label:     string   — display label
 *     icon:      ?string  — Font Awesome class
 *     component: string   — Livewire component alias
 *     props:     array    — props passed to the component on mount
 *     pinned:    bool     — true = no close button
 *     lazy:      bool     — true = defer mount until first activation
 *     mounted:   bool     — true = component has been mounted into DOM
 *   }
 */
class ModuleTabsManager extends Component
{
    /**
     * FQCN of a class implementing ProvidesNavigatorDefinition.
     * Stored as a string so Livewire can serialize it between requests.
     */
    public string $definitionClass = '';

    /** @var list<array<string, mixed>> */
    public array $openTabs = [];

    /** @var list<array<string, mixed>> Recently closed dynamic tabs (ring buffer) */
    public array $recentlyClosed = [];

    /** @var array<string, bool> tabId => bool — stale record notifications */
    public array $staleTabs = [];

    /** Standard Engine error/loading contract — see ARCHITECT_PACKAGE_PLAN.md §0.6. */
    public bool $hasError = false;

    public string $errorMessage = '';

    public bool $isLoading = false;

    // ── Lifecycle ─────────────────────────────────────────────────────────

    public function mount(string $definitionClass): void
    {
        if (! class_exists($definitionClass) || ! is_subclass_of($definitionClass, ProvidesNavigatorDefinition::class)) {
            throw new LogicException("ModuleTabsManager: [{$definitionClass}] must implement ".ProvidesNavigatorDefinition::class);
        }

        $this->definitionClass = $definitionClass;

        try {
            $this->openTabs = $this->definition()->initialOpenTabs();
        } catch (AuthorizationException) {
            // Defer to render()'s own permission check for the graceful
            // hasError/architect:unauthorized path; an empty initial tab
            // set is a safe default while that plays out.
            $this->openTabs = [];
        }
    }

    /**
     * Resolve the WorkspaceTabsDefinition fresh on each request.
     * The definition class builds a ArchitectNavigatorDefinition; we extract
     * the workspace definition from it. Safe to call multiple times.
     */
    private function definition(): WorkspaceTabsDefinition
    {
        $navDef = ($this->definitionClass)::definition();

        if (! $navDef instanceof ArchitectNavigatorDefinition || $navDef->workspaceDefinition === null) {
            throw new LogicException(
                "ModuleTabsManager: [{$this->definitionClass}::definition()] must return a ArchitectNavigatorDefinition ".
                'with a workspace definition (use Architect::make(\'navigator\')->type(\'workspace-tabs\')).'
            );
        }

        if ($navDef->permission !== null
            && ! app(PermissionResolver::class)->can(auth()->user(), $navDef->permission)) {
            throw new AuthorizationException('You do not have permission to view this workspace.');
        }

        return $navDef->workspaceDefinition;
    }

    public function render(): View
    {
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            $definition = $this->definition();
        } catch (AuthorizationException $e) {
            $this->hasError = true;
            $this->errorMessage = $e->getMessage();
            $this->dispatch('architect:unauthorized');

            return view('architect::navigator.module-tabs-manager', [
                'definition' => null,
            ]);
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while loading this workspace. Please try again.';
            report($e);

            return view('architect::navigator.module-tabs-manager', [
                'definition' => null,
            ]);
        }

        return view('architect::navigator.module-tabs-manager', [
            'definition' => $definition,
        ]);
    }

    // ── Tab mutations ─────────────────────────────────────────────────────

    /**
     * Open a dynamic tab by type + props.
     *
     * Called by:
     *  - Alpine: $wire.openTab(type, props)
     *  - Livewire event: architect:open-record
     *
     * Deduplication: if a tab with the same ID is already open, the
     * activeTabId is updated client-side via a dispatched browser event.
     *
     * @param  array<string, mixed>  $props
     */
    #[On('architect:open-record')]
    public function openTab(string $type, array $props = [], string $fallback = ''): void
    {
        $dynamicType = $this->definition()->dynamicType($type);

        if ($dynamicType === null) {
            // Unknown type — ignore silently (may be meant for another handler)
            return;
        }

        // If no props given and the type has a createUsing factory, create the
        // record now and use the returned props (e.g. ['id' => $newId]).
        // This supports the 'create' section openInTab pattern where the engine
        // dispatches architect:open-record with empty props as the "New" button.
        if (empty($props) && $dynamicType->hasCreator()) {
            $props = $dynamicType->callCreator();
        }

        $tabArray = $dynamicType->toTabArray($props);
        $tabId = $tabArray['id'];

        // Deduplication: already open → just switch to it
        foreach ($this->openTabs as $existing) {
            if ($existing['id'] === $tabId) {
                $this->dispatch('module-tabs:switch', tabId: $tabId);

                return;
            }
        }

        // Enforce maxTabs: evict oldest dynamic (non-pinned) tab
        $dynamicCount = count(array_filter($this->openTabs, fn ($t) => ! $t['pinned']));
        $def = $this->definition();

        if ($dynamicCount >= $def->maxTabs) {
            $this->evictOldestDynamic();
        }

        $this->openTabs[] = $tabArray;

        $this->dispatch('module-tabs:switch', tabId: $tabId);
        $this->persistIfEnabled();
    }

    /**
     * Close a tab by ID.
     *
     * Pinned tabs cannot be closed — the call is ignored silently.
     * The newly active tab ID is sent back via a browser event so Alpine
     * can update the store without a second server round-trip.
     */
    public function closeTab(string $tabId): void
    {
        $tab = $this->findTab($tabId);

        if ($tab === null || $tab['pinned']) {
            return;
        }

        // Add to recently-closed ring buffer
        $def = $this->definition();

        if ($def->showRecentlyClosed) {
            array_unshift($this->recentlyClosed, $tab);
            $this->recentlyClosed = array_slice($this->recentlyClosed, 0, $def->recentlyClosedMax);
        }

        $this->openTabs = array_values(
            array_filter($this->openTabs, fn ($t) => $t['id'] !== $tabId)
        );

        // Determine which tab should become active
        $nextId = $this->openTabs[0]['id'] ?? null;
        if ($nextId !== null) {
            $this->dispatch('module-tabs:switch', tabId: $nextId);
        }

        $this->persistIfEnabled();
    }

    /**
     * Restore persisted dynamic tabs on page load.
     *
     * Called once by Alpine after reading localStorage. Each entry has
     * the same shape as a tab array (type, props, label, icon etc.).
     *
     * @param  list<array<string, mixed>>  $tabs
     */
    public function restoreTabs(array $tabs): void
    {
        $def = $this->definition();

        foreach ($tabs as $tab) {
            $type = $tab['type'] ?? null;

            if ($type === null || $type === 'pinned') {
                continue;
            }

            $dynamicType = $def->dynamicType($type);

            if ($dynamicType === null) {
                continue;
            }

            $tab = $dynamicType->toTabArray($tab['props'] ?? []);

            // Skip duplicates (already open as pinned or previous restore)
            foreach ($this->openTabs as $existing) {
                if ($existing['id'] === $tab['id']) {
                    continue 2;
                }
            }

            $this->openTabs[] = $tab;
        }
    }

    /**
     * Mark a tab's content as having been mounted into the DOM.
     * Used to transition lazy pinned tabs from lazy→mounted on first activation.
     */
    public function markMounted(string $tabId): void
    {
        foreach ($this->openTabs as &$tab) {
            if ($tab['id'] === $tabId) {
                $tab['mounted'] = true;
                break;
            }
        }
    }

    /**
     * Mark a tab as stale (cross-tab record-updated notification).
     */
    #[On('architect:record-updated')]
    public function handleRecordUpdated(string $type, int|string $id): void
    {
        if (! $this->definition()->notifyStaleRecords) {
            return;
        }

        foreach ($this->openTabs as $tab) {
            if ($tab['type'] === $type && ($tab['props']['id'] ?? null) == $id) {
                $this->staleTabs[$tab['id']] = true;
            }
        }
    }

    /**
     * Clear the stale indicator on a tab and re-mount it by toggling
     * its key, forcing Livewire to remount the child component fresh.
     */
    public function refreshTab(string $tabId): void
    {
        unset($this->staleTabs[$tabId]);

        // Force remount: append a version suffix to the tab ID
        foreach ($this->openTabs as &$tab) {
            if ($tab['id'] === $tabId) {
                $tab['version'] = ($tab['version'] ?? 0) + 1;
                break;
            }
        }
    }

    // ── Internal helpers ──────────────────────────────────────────────────

    /**
     * @return array<string, mixed>|null
     */
    private function findTab(string $tabId): ?array
    {
        foreach ($this->openTabs as $tab) {
            if ($tab['id'] === $tabId) {
                return $tab;
            }
        }

        return null;
    }

    /**
     * Remove the oldest non-pinned tab to make room for a new one.
     */
    private function evictOldestDynamic(): void
    {
        $def = $this->definition();

        foreach ($this->openTabs as $index => $tab) {
            if (! $tab['pinned']) {
                // Add to recently-closed before evicting
                if ($def->showRecentlyClosed) {
                    array_unshift($this->recentlyClosed, $tab);
                    $this->recentlyClosed = array_slice($this->recentlyClosed, 0, $def->recentlyClosedMax);
                }

                array_splice($this->openTabs, $index, 1);
                break;
            }
        }
    }

    /**
     * Dispatch a browser event to let Alpine persist the current tab list.
     */
    private function persistIfEnabled(): void
    {
        if (! $this->definition()->persist) {
            return;
        }

        $this->dispatch('module-tabs:persist', openTabs: $this->openTabs);
    }
}
