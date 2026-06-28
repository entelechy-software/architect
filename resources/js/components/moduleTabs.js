/**
 * moduleTabs — Alpine store for the ModuleTabs workspace.
 *
 * Registered in app.js inside the livewire:init event handler.
 * Accessed in Blade as: $store.moduleTabs
 *
 * Responsibilities:
 *   - Track the active tab ID (switching is pure JS, no Livewire call)
 *   - Dirty / saved state per tab
 *   - localStorage persistence (read on init, write on persist event)
 *   - Tab bar overflow tracking (visible vs overflowed tabs)
 *   - Context menu state
 *   - Recently-closed and switcher palette integration
 *   - Global architect:open-tab fallback (no ModuleTabsManager on page)
 *   - beforeunload / sidebar-nav dirty-tab warning
 */

const STORAGE_PREFIX = 'arch_tabs_';

// ── Helpers ───────────────────────────────────────────────────────────────

function storageKey(definitionKey) {
    return STORAGE_PREFIX + definitionKey;
}

function readStorage(key) {
    try {
        const raw = localStorage.getItem(storageKey(key));
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function writeStorage(key, openTabs) {
    try {
        // Only persist dynamic (non-pinned) tabs
        const dynamic = openTabs.filter(t => !t.pinned);
        localStorage.setItem(storageKey(key), JSON.stringify({ openTabs: dynamic }));
    } catch {
        // Storage quota exceeded — ignore silently
    }
}

// ── Store factory ─────────────────────────────────────────────────────────

export function registerModuleTabs(Alpine) {

    // ── Global architect:open-tab fallback ───────────────────────────────
    // If no ModuleTabsManager is mounted, navigate to the fallback URL.
    document.addEventListener('architect:open-tab', (e) => {
        if (!e.detail) return;
        // If the Alpine store has an active wire context, the Livewire
        // #[On] handler will have already fired — don't double-navigate.
        // We detect this by checking whether any .arch-module-tabs element
        // exists in the DOM.
        if (document.querySelector('.arch-module-tabs')) return;

        const fallback = e.detail.fallback;
        if (fallback) {
            window.location.href = fallback;
        }
    });

    // ── Alpine store ────────────────────────────────────────────────────
    Alpine.store('moduleTabs', {

        // ── State ────────────────────────────────────────────────────
        activeId: null,
        definition: null,

        /** @type {{ [tabId: string]: boolean }} */
        dirtyTabs: {},

        /** @type {{ [tabId: string]: boolean }} recently-saved flash */
        savedTabs: {},

        /** @type {{ [tabId: string]: ReturnType<typeof setTimeout> }} */
        _savedTimers: {},

        /** @type {Array} full open-tabs list (mirror of Livewire state) */
        openTabs: [],

        /** @type {Array} tabs that don't fit in the bar (computed) */
        overflowTabs: [],
        overflowCount: 0,

        /** @type {{ open: boolean, x: number, y: number, tabId: string|null }} */
        contextMenu: { open: false, x: 0, y: 0, tabId: null },

        // ── Init ──────────────────────────────────────────────────────

        /**
         * Called once from the Livewire component's x-init.
         * Reads localStorage and calls $wire.restoreTabs() if persistence
         * is enabled and stored tabs exist.
         *
         * Note: deliberately NOT named 'init' — Alpine.js auto-calls init() on
         * stores with no arguments. This method requires $wire and definition,
         * so it must be invoked explicitly from the Livewire component's x-init.
         */
        async setup(wire, definition) {
            this.definition = definition;

            // Set active to first pinned tab
            const firstPinned = (definition.pinnedTabs || [])[0];
            if (firstPinned) {
                this.activeId = firstPinned.id || ('pinned-' + firstPinned.label.toLowerCase().replace(/[^a-z0-9]+/g, '-'));
            }

            // Restore persisted dynamic tabs
            if (definition.persist) {
                const stored = readStorage(definition.key);
                if (stored && Array.isArray(stored.openTabs) && stored.openTabs.length > 0) {
                    await wire.restoreTabs(stored.openTabs);
                }
            }

            // Register keyboard shortcuts
            this._registerShortcuts(wire, definition);

            // Register dirty-nav warning
            this._registerDirtyNavWarning(wire);

            // Observe overflow after first render
            Promise.resolve().then(() => this._updateOverflow());
        },

        // ── Tab switching ──────────────────────────────────────────────

        /**
         * Switch to a tab — pure Alpine, no Livewire call.
         * Focuses the content panel for keyboard accessibility.
         */
        switch(tabId) {
            this.activeId = tabId;
            Promise.resolve().then(() => {
                const panel = document.querySelector(
                    `.arch-module-tabs__panel[data-tab-id="${tabId}"]`
                );
                if (panel) panel.focus();
            });
        },

        /**
         * Switch to a tab, triggering lazy mount if needed.
         * Used by tab-bar button clicks and overflow popover.
         */
        switchTo(tabId, wire) {
            // Trigger lazy mount via Livewire if not yet mounted
            const tab = this.openTabs.find(t => t.id === tabId);
            if (tab && tab.lazy && !tab.mounted) {
                wire.markMounted(tabId);
            }
            this.switch(tabId);
        },

        // ── Dirty / saved state ────────────────────────────────────────

        markDirty(tabId, isDirty) {
            if (isDirty) {
                this.dirtyTabs[tabId] = true;
            } else {
                delete this.dirtyTabs[tabId];
            }
        },

        isDirty(tabId) {
            return !!this.dirtyTabs[tabId];
        },

        markSaved(tabId) {
            // Brief green-flash state — cleared after 1.5s
            this.savedTabs[tabId] = true;
            clearTimeout(this._savedTimers[tabId]);
            this._savedTimers[tabId] = setTimeout(() => {
                delete this.savedTabs[tabId];
            }, 1500);
            // Also clear dirty state on save
            delete this.dirtyTabs[tabId];
        },

        isSaved(tabId) {
            return !!this.savedTabs[tabId];
        },

        isPinned(tabId) {
            const tab = this.openTabs.find(t => t.id === tabId);
            return tab ? tab.pinned : false;
        },

        hasDirtyTabs() {
            return Object.keys(this.dirtyTabs).length > 0;
        },

        // ── Close helpers ──────────────────────────────────────────────

        async requestClose(tabId, wire) {
            if (this.isDirty(tabId)) {
                const ok = await window.architectConfirm(
                    'This tab has unsaved changes. Close it and discard changes?',
                    'Discard & Close'
                );
                if (!ok) return;
                this.markDirty(tabId, false);
            }
            wire.closeTab(tabId);
        },

        async closeOthers(tabId, wire) {
            const toClose = this.openTabs
                .filter(t => !t.pinned && t.id !== tabId)
                .map(t => t.id);
            for (const id of toClose) {
                await this.requestClose(id, wire);
            }
        },

        async closeAllToRight(tabId, wire) {
            const idx = this.openTabs.findIndex(t => t.id === tabId);
            if (idx === -1) return;
            const toClose = this.openTabs
                .slice(idx + 1)
                .filter(t => !t.pinned)
                .map(t => t.id);
            for (const id of toClose) {
                await this.requestClose(id, wire);
            }
        },

        async closeAll(wire) {
            const toClose = this.openTabs
                .filter(t => !t.pinned)
                .map(t => t.id);
            for (const id of toClose) {
                await this.requestClose(id, wire);
            }
        },

        // ── Persistence ────────────────────────────────────────────────

        persist(openTabs) {
            this.openTabs = openTabs;
            this._updateOverflow();
            if (this.definition && this.definition.persist) {
                writeStorage(this.definition.key, openTabs);
            }
        },

        // ── Overflow tracking ──────────────────────────────────────────

        _updateOverflow() {
            const bar = document.querySelector('.arch-module-tabs__tabs');
            if (!bar || !this.definition?.showOverflowPopover) {
                this.overflowCount = 0;
                this.overflowTabs = [];
                return;
            }

            const barRight = bar.getBoundingClientRect().right;
            const buttons = Array.from(bar.querySelectorAll('.arch-module-tabs__tab'));
            const visible = [];
            const overflow = [];

            buttons.forEach((btn, i) => {
                const rect = btn.getBoundingClientRect();
                if (rect.right > barRight + 2) {
                    overflow.push(this.openTabs[i]);
                } else {
                    visible.push(this.openTabs[i]);
                }
            });

            this.overflowTabs = overflow.filter(Boolean);
            this.overflowCount = this.overflowTabs.length;
        },

        // ── Context menu ───────────────────────────────────────────────

        openContextMenu(event, tabId) {
            this.contextMenu = {
                open: true,
                x: Math.min(event.clientX, window.innerWidth - 200),
                y: event.clientY,
                tabId,
            };
        },

        // ── Keyboard shortcuts ─────────────────────────────────────────

        _registerShortcuts(wire, definition) {
            if (!definition.enableSwitcherPalette) return;

            document.addEventListener('keydown', (e) => {
                // Ctrl+Shift+T — open tab switcher
                if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                    e.preventDefault();
                    this._openSwitcherPalette(wire);
                }
            });
        },

        _openSwitcherPalette(wire) {
            // Integrate with ninjaKeys — push open tabs as temporary items
            const ninja = document.querySelector('ninja-keys');
            if (!ninja) return;

            const items = this.openTabs.map(tab => ({
                id: 'tab-switch-' + tab.id,
                title: tab.label,
                icon: tab.icon
                    ? `<i class="${tab.icon}" style="width:1em;text-align:center"></i>`
                    : '<i class="fas fa-circle" style="width:1em;text-align:center"></i>',
                section: 'Open Tabs',
                handler: () => this.switchTo(tab.id, wire),
            }));

            ninja.data = items;
            ninja.open();
        },

        // ── Dirty navigation warning ───────────────────────────────────

        _registerDirtyNavWarning(wire) {
            // Browser close / reload
            window.addEventListener('beforeunload', (e) => {
                if (this.hasDirtyTabs()) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Sidebar navigation link interception
            document.addEventListener('click', async (e) => {
                const link = e.target.closest('a[href]');
                if (!link) return;

                // Only intercept same-origin internal navigation links
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                if (!this.hasDirtyTabs()) return;
                if (!document.querySelector('.arch-module-tabs')) return;

                e.preventDefault();

                const ok = await window.architectConfirm(
                    'You have unsaved changes in open tabs. Leave this page and discard them?',
                    'Leave Page'
                );
                if (ok) {
                    // Clear dirty state so beforeunload doesn't re-trigger
                    this.dirtyTabs = {};
                    window.location.href = href;
                }
            }, true); // capture phase so we intercept before Livewire
        },
    });
}
