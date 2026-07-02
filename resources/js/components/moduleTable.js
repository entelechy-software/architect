/**
 * moduleTable — Alpine.js data component for the ModuleTable engine.
 *
 * Extracted from engine.blade.php to maintain a clean separation of concerns.
 * All ModuleTable JavaScript logic lives here; the blade template contains
 * only structural HTML and Livewire/Alpine binding attributes.
 *
 * Architecture:
 *   - One Alpine component instance per ModuleTable on the page.
 *   - Duplicate-instance detection guards against two tables with the same
 *     definition class appearing simultaneously (they would collide on the
 *     Alpine store key and on Livewire event routing).
 *   - A page-wide Alpine store (`window.Alpine.store('moduleTable')`) mirrors
 *     each instance's reactive state so external components can subscribe
 *     without tight coupling.
 *
 * jQuery dependency:
 *   The bookmark-filters Lookup widget requires jQuery + Lookup. All other
 *   DOM operations in this file use the vanilla Web API.
 *
 * @module moduleTable
 */

/**
 * Guard flag — the Livewire morph tooltip hook only needs to be registered
 * once per page load, not once per component instance.
 *
 * @type {boolean}
 */
let _tooltipHookRegistered = false;

/**
 * Registers a single Livewire morph hook for tooltip setup after each
 * Livewire DOM update.
 *
 * Called from init() on every instance but the registration is idempotent:
 * subsequent calls are no-ops once the hook is in place.
 *
 * @returns {void}
 */
/**
 * Initialise native CSS tooltips after each Livewire morph cycle.
 *
 * JS tooltip plugins are not used. Buttons rely on the native `title`
 * attribute, which modern browsers surface via the OS tooltip layer.
 * This is zero-JS, zero-dependency, and accessible.
 *
 * The hook body is intentionally empty — we keep the function so that call
 * sites in init() do not need to change, and so future implementors know
 * where to plug in a fancier Alpine Floating-UI tooltip if desired.
 */
function ensureTooltipHook() {
    if (_tooltipHookRegistered) {
        return;
    }
    _tooltipHookRegistered = true;
    // No-op: native `title` attribute provides browser tooltips without JS.
}

/**
 * Registers the `moduleTable` Alpine data component with the provided
 * Alpine instance.
 *
 * Call this inside a `livewire:init` listener so Alpine is guaranteed to be
 * available:
 *
 * ```js
 * document.addEventListener('livewire:init', () => {
 *     registerModuleTable(window.Alpine);
 * });
 * ```
 *
 * @param {object} Alpine - The Alpine.js global object.
 * @returns {void}
 */
export function registerModuleTable(Alpine) {

    /**
     * Alpine data factory for ModuleTable.
     *
     * Options are the only values the blade template needs to inject:
     * everything else is self-contained JavaScript.
     *
     * @param {object}  options
     * @param {string}  options.instanceKey            - Unique key for this instance (md5 of wire component id).
     * @param {string}  options.tablePrefix             - localStorage key namespace (table + user id).
     * @param {boolean} options.persistenceEnabled      - Whether filter persistence is active for this table.
     * @param {boolean} options.bookmarkFiltersEnabled    - Whether filter bookmarking is active for this table.
     * @param {object}  options.cascadeChildren           - Map of parent editKey → array of dependent child editKeys.
     * @param {string}  options.definitionMd5             - md5 hash of the definition class (for Lookup element IDs).
     * @param {number}  [options.autoRefreshSeconds=0]    - Seconds between automatic refreshes (0 = disabled).
    * @param {string|null} [options.autoRefreshFingerprintOn=null] - Optional fingerprint key for skip-render checks.
     * @param {string|null} [options.supersearchHookId=null] - Livewire component ID to broadcast hook-unmounted on destroy.
     *
     * @returns {object} Alpine data object.
     */
    Alpine.data('moduleTable', (options) => ({

        /* ── UI state ────────────────────────────────────────────────── */
        showEditSuccess:  false,
        showEditError:    false,
        editErrorMessage: '',

        /** Flags a duplicate-instance error; init() sets this and bails. */
        _duplicateError: false,

        /* ── Options (passed from blade) ─────────────────────────────── */
        _instanceKey:           options.instanceKey,
        _tablePrefix:           options.tablePrefix,
        _persistenceEnabled:    options.persistenceEnabled,
        _bookmarkFiltersEnabled: options.bookmarkFiltersEnabled,

        /* ── Auto-refresh ────────────────────────────────────────────── */
        /** Configured interval in seconds; 0 means the feature is disabled. */
        _arInterval:  options.autoRefreshSeconds || 0,
        /** Seconds remaining until the next automatic refresh. */
        _arRemaining: options.autoRefreshSeconds || 0,
        /** Optional fingerprint key used by server-side pre-check action. */
        _arFingerprintOn: options.autoRefreshFingerprintOn || null,
        /** Length of the manual post-refresh lock-out ring in seconds. */
        _arManualDuration: 3,
        /** Seconds remaining on the manual post-refresh lock-out ring. */
        _arManualRemaining: 0,
        /** True for 2 seconds after a refresh fires (manual or automatic). */
        _arLoading:   false,
        /** True while the lightweight fingerprint check request is in flight. */
        _arChecking: false,
        /** setInterval handle; null when no countdown is running. */
        _arTick:      null,
        /** setInterval handle for the short manual lock-out ring animation. */
        _arManualTick: null,

        /* ── Filter slide-over ───────────────────────────────────────── */
        /** Controls the Alpine-driven filter slide-over panel visibility. */
        filtersOpen: false,

        /* ── Filter persistence ──────────────────────────────────────── */
        persistEnabled: false,

        /* ── Bookmarked filters ──────────────────────────────────────── */
        bookmarkedFilters: [],

        /**
         * Pre-computed reverse map: stringified filter set → bookmark name.
         * Built once per bookmarkedFilters mutation by _rebuildBookmarkLookup()
         * and read on every filter change instead of doing an O(n) JSON.stringify
         * scan inside _activeBookmarkedFilterLabel().
         *
         * @type {Object<string, string>}
         */
        _bookmarkLookup: Object.create(null),

        /**
         * Cache key (hash of bookmarkedFilters) used by
         * initBookmarkedFiltersPicker() to detect whether the bookmark
         * list actually changed since the last build, so reopening the
         * filter offcanvas does not pay for a full Lookup destroy+rebuild
         * when nothing meaningful has changed.
         *
         * @type {string|null}
         */
        _bookmarkFilterHash: null,

        /* ── Column visibility ───────────────────────────────────────── */
        /**
         * Array of column keys the user has chosen to hide. Persisted in
         * localStorage so the preference survives page reloads.
         *
         * @type {string[]}
         */
        hiddenCols: JSON.parse(
            localStorage.getItem('moduleTable_' + options.definitionMd5 + '_hiddenColumns') || '[]'
        ),

        /* ── Cell-mode inline edit ───────────────────────────────────── */
        /**
         * Tracks the single cell currently open for inline editing.
         * Only one cell can be active at a time; clicking a different cell
         * auto-commits the previous one if a change was made.
         *
         * @type {object}
         */
        inlineEdit: {
            rowId:         null,
            columnKey:     null,
            value:         null,
            originalValue: null,
            error:         null,

            /**
             * Suppresses stale blur events that fire as focus moves between
             * cells. Set to `true` for one microtask tick during a cell
             * transition so the outgoing cell's blur handler does not
             * accidentally cancel the incoming cell's edit state.
             *
             * @type {boolean}
             */
            _switching: false,

            /**
             * Opens inline edit for the given cell.
             * If another cell is already open and its value has changed,
             * that edit is committed before the new cell opens.
             *
             * @param {number} rowId      - Primary key of the row being edited.
             * @param {string} columnKey  - Column identifier.
             * @param {*}      value      - Current cell value (pre-edit).
             * @param {object} $wire      - Livewire component proxy.
             * @returns {void}
             */
            start(rowId, columnKey, value, $wire) {
                // Raise the switching flag so blur handlers on the
                // outgoing cell do not interfere with this transition.
                this._switching = true;
                Promise.resolve().then(() => { this._switching = false; });

                // Auto-commit unsaved changes when jumping to a different cell.
                if (this.rowId !== null && (this.rowId !== rowId || this.columnKey !== columnKey)) {
                    if (this.value !== this.originalValue) {
                        $wire.call('saveEdit', this.rowId, this.columnKey, this.value);
                    }
                }

                this.rowId         = rowId;
                this.columnKey     = columnKey;
                this.value         = value;
                this.originalValue = value;
                this.error         = null;
            },

            /**
             * Cancels the active inline edit, discarding any unsaved changes.
             *
             * @returns {void}
             */
            cancel() {
                this.rowId         = null;
                this.columnKey     = null;
                this.value         = null;
                this.originalValue = null;
                this.error         = null;
            },

            /**
             * Commits the active edit unconditionally, saving the current
             * value to the server.
             *
             * @param {object} $wire - Livewire component proxy.
             * @returns {void}
             */
            commit($wire) {
                if (this.rowId === null) return;
                $wire.call('saveEdit', this.rowId, this.columnKey, this.value);
                this.cancel();
            },

            /**
             * Commits only if the value has actually changed, otherwise
             * just cancels the edit.
             *
             * @param {object} $wire - Livewire component proxy.
             * @returns {void}
             */
            commitIfChanged($wire) {
                if (this.rowId === null) return;
                if (this.value !== this.originalValue) {
                    this.commit($wire);
                } else {
                    this.cancel();
                }
            },

            /**
             * Cancels only if the specified cell is still the active one
             * and we are not mid-transition between cells.
             *
             * @param {number} rowId     - Row to match.
             * @param {string} columnKey - Column to match.
             * @returns {void}
             */
            cancelIfActive(rowId, columnKey) {
                if (this._switching) return;
                if (this.rowId === rowId && this.columnKey === columnKey) {
                    this.cancel();
                }
            },

            /**
             * Commits the changed value only if the specified cell is
             * still the active one and we are not mid-transition.
             *
             * @param {number} rowId     - Row to match.
             * @param {string} columnKey - Column to match.
             * @param {object} $wire     - Livewire component proxy.
             * @returns {void}
             */
            commitIfChangedAndActive(rowId, columnKey, $wire) {
                if (this._switching) return;
                if (this.rowId === rowId && this.columnKey === columnKey) {
                    this.commitIfChanged($wire);
                }
            },
        },

        /* ── Row-mode inline edit ────────────────────────────────────── */
        /**
         * Holds the complete editable state for one row while it is open
         * in row-edit mode. Row-mode is used when columns have cascade or
         * cross-field dependencies that cannot be resolved one cell at a time.
         *
         * @type {object}
         */
        rowEdit: {
            rowId:          null,
            values:         {},
            originalValues: {},
            errors:         {},

            /**
             * Map of parent editKey → array of child editKeys that must be
             * cleared when the parent value changes.
             * Populated by init() from the options passed by the blade.
             *
             * @type {object}
             */
            cascadeChildren: {},

            /**
             * Returns true if the given row is currently open in row-edit mode.
             *
             * @param {number} rowId - Row primary key.
             * @returns {boolean}
             */
            isActive(rowId) {
                return this.rowId === rowId;
            },

            /**
             * Opens row-edit mode for the given row, seeding the values map
             * from the row's current data. Cancels any previously open row.
             *
             * @param {number} rowId         - Row primary key.
             * @param {object} initialValues - Map of editKey → current value.
             * @returns {void}
             */
            start(rowId, initialValues) {
                if (this.rowId !== null && this.rowId !== rowId) {
                    this.cancel();
                }
                this.rowId          = rowId;
                this.values         = Object.assign({}, initialValues);
                this.originalValues = Object.assign({}, initialValues);
                this.errors         = {};
            },

            /**
             * Discards all pending changes and closes row-edit mode.
             *
             * @returns {void}
             */
            cancel() {
                this.rowId          = null;
                this.values         = {};
                this.originalValues = {};
                this.errors         = {};
            },

            /**
             * Updates a single field value, then cascade-clears any
             * dependent child fields defined in cascadeChildren.
             *
             * @param {string} editKey - The field key being changed.
             * @param {*}      value   - The new value.
             * @returns {void}
             */
            setValue(editKey, value) {
                this.values[editKey] = value;

                // Clear dependent children so stale values do not pass
                // server-side validation after a parent changes.
                const children = this.cascadeChildren[editKey];
                if (children) {
                    children.forEach((childKey) => { this.values[childKey] = ''; });
                }

                // Clear any existing error for this field on change.
                if (this.errors[editKey]) {
                    delete this.errors[editKey];
                }
            },

            /**
             * Submits the row's current values to the server for validation
             * and persistence. The row stays open until either `edit-saved`
             * (success → close) or `row-edit:errors` (failure → show errors)
             * is received.
             *
             * @param {object} $wire - Livewire component proxy.
             * @returns {void}
             */
            save($wire) {
                if (this.rowId === null) return;
                $wire.call('saveRow', this.rowId, this.values);
            },

            /**
             * Applies server-returned field-level validation errors to the
             * row's error map so individual cells can display their messages.
             *
             * @param {number} rowId  - The row whose errors are being applied.
             * @param {object} errors - Laravel validation error bag (key → array of messages).
             * @returns {void}
             */
            applyErrors(rowId, errors) {
                if (this.rowId !== rowId) return;

                // Flatten Laravel's array-of-messages format to a single
                // string per key for simpler x-text binding.
                const flat = {};
                Object.keys(errors).forEach((k) => {
                    flat[k] = (errors[k] && errors[k][0]) || '';
                });
                this.errors = flat;
            },
        },

        /* ── Column visibility ───────────────────────────────────────── */

        /**
         * Returns true if the column with the given key is currently hidden.
         *
         * @param {string} key - Column identifier.
         * @returns {boolean}
         */
        isColHidden(key) {
            return this.hiddenCols.includes(key);
        },

        /**
         * Toggles the visibility of a column and persists the updated list
         * to localStorage.
         *
         * @param {string} key - Column identifier.
         * @returns {void}
         */
        toggleCol(key) {
            const idx = this.hiddenCols.indexOf(key);
            if (idx === -1) {
                this.hiddenCols.push(key);
            } else {
                this.hiddenCols.splice(idx, 1);
            }
            localStorage.setItem(
                'moduleTable_' + options.definitionMd5 + '_hiddenColumns',
                JSON.stringify(this.hiddenCols)
            );
        },

        /* ── Filter persistence ──────────────────────────────────────── */

        /**
         * Toggles filter persistence on/off and saves the preference to
         * localStorage. When enabling, immediately saves the current filters.
         *
         * @returns {void}
         */
        togglePersist() {
            this.persistEnabled = !this.persistEnabled;
            if (this.persistEnabled) {
                localStorage.setItem(this._tablePrefix + 'persistEnabled', '1');
                this.persistFilters(this.$wire.filters);
            } else {
                localStorage.removeItem(this._tablePrefix + 'persistEnabled');
                localStorage.removeItem(this._tablePrefix + 'persistedFilters');
            }
        },

        /**
         * Saves the current filter state to localStorage if persistence is
         * currently enabled.
         *
         * @param {object} filters - The current Livewire filters object.
         * @returns {void}
         */
        persistFilters(filters) {
            if (this.persistEnabled) {
                localStorage.setItem(
                    this._tablePrefix + 'persistedFilters',
                    JSON.stringify(filters)
                );
            }
        },

        /* ── Filter slide-over open / close ────────────────────────────── */

        /**
         * Open the filter slide-over.
         * Fires `architect:filters-opened` (replaces the older panel-open event)
         * so Lookup filter widgets initialise after the panel is visible.
         */
        openFilters() {
            this.filtersOpen = true;
            this.$nextTick(() => {
                document.dispatchEvent(new CustomEvent('architect:filters-opened', {
                    bubbles: true,
                    detail: { instanceKey: this._instanceKey },
                }));
            });
        },

        /**
         * Close the filter slide-over and blur focused inputs to prevent
         * Safari's on-screen keyboard from staying open.
         */
        closeFilters() {
            this.filtersOpen = false;
            if (document.activeElement instanceof HTMLElement) {
                document.activeElement.blur();
            }
        },

        /* ── Bookmarked filters ──────────────────────────────────────── */

        /**
         * Returns the name of the bookmarked filter that matches the current
         * active filters, or an empty string if no bookmark matches.
         *
         * Used to display the bookmark name inside the filter badge.
         *
         * @returns {string}
         */
        _activeBookmarkedFilterLabel() {
            if (!this.bookmarkedFilters.length || !Object.keys(this.$wire.filters).length) {
                return '';
            }
            // O(1) lookup against the pre-computed map (rebuilt only when
            // bookmarkedFilters mutates), replacing the previous O(n)
            // JSON.stringify scan that ran on every filter change.
            return this._bookmarkLookup[JSON.stringify(this.$wire.filters)] || '';
        },

        /**
         * Rebuild the {filterHash → bookmark name} lookup whenever the
         * bookmarkedFilters array mutates. Called from init() and watched
         * via $watch('bookmarkedFilters').
         *
         * @returns {void}
         */
        _rebuildBookmarkLookup() {
            const map = Object.create(null);
            for (let i = 0; i < this.bookmarkedFilters.length; i++) {
                const sf = this.bookmarkedFilters[i];
                map[JSON.stringify(sf.filters)] = sf.name;
            }
            this._bookmarkLookup = map;
        },

        /**
         * Prompts the user for a name and saves the current filter state as
         * a named bookmark in localStorage.
         *
         * Uses SweetAlert if available; falls back to window.prompt.
         *
         * @returns {void}
         */
        bookmarkCurrentFilter() {
            const offcanvasEl = document.getElementById('arch-filter-panel-' + this._instanceKey);

            if (this.bookmarkedFilters.length >= 10) {
                if (window.Swal) {
                    window.Swal.fire({
                        target: offcanvasEl,
                        icon:   'warning',
                        text:   'Maximum of 10 bookmarked filters reached. Delete one first.',
                    });
                } else {
                    alert('Maximum of 10 bookmarked filters reached. Delete one first.');
                }
                return;
            }

            /**
             * Saves the bookmark once the user has provided a name.
             *
             * @param {string} name - User-supplied bookmark name.
             * @returns {void}
             */
            const doSave = (name) => {
                name = (name || '').trim();
                if (!name) return;
                this.bookmarkedFilters.push({
                    name:    name,
                    filters: Object.assign({}, this.$wire.filters),
                });
                localStorage.setItem(
                    this._tablePrefix + 'bookmarkedFilters',
                    JSON.stringify(this.bookmarkedFilters)
                );
            };

            if (window.Swal) {
                window.Swal.fire({
                    target:           offcanvasEl,
                    title:            'Name this bookmark',
                    input:            'text',
                    inputPlaceholder: 'e.g. Current members only',
                    inputAttributes:  { maxlength: '50', autocomplete: 'off' },
                    showCancelButton: true,
                    confirmButtonColor: '#2c7be5',
                    cancelButtonColor:  '#6c757d',
                    confirmButtonText:  'Bookmark',
                    cancelButtonText:   'Cancel',
                    reverseButtons:     true,
                    inputValidator: (value) => {
                        if (!value || !value.trim()) return 'Please enter a name.';
                    },
                }).then((result) => {
                    if (result.isConfirmed) doSave(result.value);
                });
            } else {
                doSave(window.prompt('Name this bookmark:'));
            }
        },

        /**
         * Applies a saved bookmark's filters to the table.
         *
         * @param {number} idx - Index of the bookmark in bookmarkedFilters.
         * @returns {void}
         */
        applyBookmarkedFilter(idx) {
            this.$wire.set('filters', Object.assign({}, this.bookmarkedFilters[idx].filters));
            this.$wire.set('page', 1);
        },

        /**
         * Removes a bookmark by index and persists the updated list.
         *
         * @param {number} idx - Index of the bookmark to remove.
         * @returns {void}
         */
        deleteBookmarkedFilter(idx) {
            this.bookmarkedFilters.splice(idx, 1);
            localStorage.setItem(
                this._tablePrefix + 'bookmarkedFilters',
                JSON.stringify(this.bookmarkedFilters)
            );
        },

        /**
         * Re-initialises the bookmarked-filters picker using a vanilla-JS
         * native <select> — zero jQuery, zero Lookup.
         *
         * The previous implementation used Lookup with jQuery. This version
         * populates a plain <select> element that the engine.blade.php template
         * binds to via a change event listener. The method pre-selects the active
         * bookmark so the displayed state is always correct.
         *
         * Called after architect:filters-opened and after bookmarkedFilters changes.
         *
         * @returns {void}
         */
        initBookmarkedFiltersPicker() {
            const selectEl = document.getElementById(
                'mt-bookmarked-filters-select-' + options.definitionMd5
            );
            if (!selectEl) return;

            const currentFilters = JSON.stringify(this.$wire.filters);

            // Rebuild the native <select> option list.
            selectEl.innerHTML = '<option value="">Apply a bookmarked filter…</option>';

            let activeIdx = -1;
            this.bookmarkedFilters.forEach((sf, idx) => {
                const opt       = document.createElement('option');
                opt.value       = String(idx);
                opt.textContent = sf.name;
                selectEl.appendChild(opt);
                if (JSON.stringify(sf.filters) === currentFilters) {
                    activeIdx = idx;
                }
            });

            selectEl.value = activeIdx === -1 ? '' : String(activeIdx);

            // Wire up change handler — remove old one first to stay idempotent.
            selectEl.removeEventListener('change', selectEl._sumsBmHandler);
            const self = this;
            selectEl._sumsBmHandler = (e) => {
                const v = e.target.value;
                if (v === '') {
                    self.$wire.call('clearFilters');
                } else {
                    self.applyBookmarkedFilter(parseInt(v, 10));
                }
            };
            selectEl.addEventListener('change', selectEl._sumsBmHandler);
        },

        /* ── Auto-refresh methods ────────────────────────────────────── */

        /**
         * Trigger a manual or automatic data refresh.
         *
         * Calls $wire.$refresh(), sets a 2-second loading lock-out, and
         * (when auto-refresh is configured) resets the countdown ring and
         * restarts the tick so the next automatic refresh fires on time.
         *
         * @returns {void}
         */
        arRefresh() {
            if (this._arLoading) return;
            this.$wire.$refresh();
            this._arLoading = true;
            if (this._arInterval) {
                // Stop the countdown during the lock-out so the next full
                // interval only begins once the button re-enables.
                clearInterval(this._arTick);
                this._arTick = null;
                this._arRemaining = this._arInterval;
            } else {
                this._startManualArTick();
            }
            setTimeout(() => {
                this._arLoading = false;
                if (this._arInterval) {
                    this._restartArTick();
                } else {
                    clearInterval(this._arManualTick);
                    this._arManualTick = null;
                    this._arManualRemaining = 0;
                }
            }, 3000);
        },

        /**
         * Start a short-lived smooth countdown for the manual refresh ring.
         *
         * @returns {void}
         */
        _startManualArTick() {
            clearInterval(this._arManualTick);
            this._arManualRemaining = this._arManualDuration;

            const startedAt = Date.now();
            const durationMs = this._arManualDuration * 1000;

            this._arManualTick = setInterval(() => {
                const elapsedMs = Date.now() - startedAt;
                const remaining = Math.max(0, durationMs - elapsedMs) / 1000;

                this._arManualRemaining = remaining;

                if (remaining <= 0) {
                    clearInterval(this._arManualTick);
                    this._arManualTick = null;
                }
            }, 100);
        },

        /**
         * Clear any existing tick and start a fresh 1-second countdown.
         *
         * Each tick decrements _arRemaining by 1. When it reaches 0,
         * arRefresh() is called automatically. Ticks are skipped while
         * _arLoading is true (the 2-second post-refresh lock-out), so the
         * full interval is always honoured from the moment loading clears.
         *
         * @returns {void}
         */
        _restartArTick() {
            clearInterval(this._arTick);
            this._arTick = setInterval(() => {
                this._arRemaining = Math.max(0, this._arRemaining - 1);
                if (this._arRemaining === 0 && !this._arLoading && !this._arChecking) {
                    if (this._arFingerprintOn) {
                        this._arChecking = true;
                        Promise.resolve(this.$wire.call('checkAutoRefreshFingerprint', this._instanceKey))
                            .finally(() => {
                                this._arChecking = false;
                                this._arRemaining = this._arInterval;
                            });

                        return;
                    }

                    this.arRefresh();
                }
            }, 1000);
        },

        /* ── Lifecycle ───────────────────────────────────────────────── */

        /**
         * Alpine init hook — called automatically when the component mounts.
         *
         * Responsibilities:
         *  1. Duplicate-instance guard (prevents two of the same table on a page).
         *  2. Registers the global Alpine store entry for this instance.
         *  3. Wires panel-close blur handling for the focused input
         *     (prevents a Safari bug where the keyboard stays open).
         *  4. Restores persisted filters from localStorage if persistence is on.
         *  5. Sets up the $wire.filters watcher to keep persistence in sync.
         *  6. Migrates bookmarks from the legacy 'savedFilters' key.
         *  7. Registers the Livewire 'module-table:filters-cleared' event handler.
         *  8. Ensures the tooltip morph hook is registered.
         *
         * @returns {void}
         */
        init() {
            // Seed cascade children from the options passed by the blade.
            this.rowEdit.cascadeChildren = options.cascadeChildren || {};

            // ── 1 & 2. Store registration + duplicate guard ─────────── //

            // Ensure the global store namespace exists. Alpine is always
            // booted by the time init() runs (Livewire guarantees this).
            if (!window.Alpine.store('moduleTable')) {
                window.Alpine.store('moduleTable', {});
            }

            const store = window.Alpine.store('moduleTable');

            if (store[this._instanceKey] && store[this._instanceKey]._registered) {
                // The same definition is already mounted — flag the error
                // and skip the rest of init to prevent store key collisions
                // and doubled Livewire event handling.
                console.error(
                    '[ModuleTable] Duplicate instance detected. A table can only appear once per page.'
                );
                this._duplicateError = true;
                return;
            }

            store[this._instanceKey] = { _registered: true };

            // ── Cleanup on unmount ───────────────────────────────────── //
            // When Livewire removes this component (e.g. @if toggle in parent),
            // clear the store slot so the same definition can be re-mounted later.
            // $cleanup magic is not available in this build; write to _x_cleanups directly.
            {
                const instanceKey = this._instanceKey;
                const el = this.$el;
                if (!el._x_cleanups) el._x_cleanups = [];
                el._x_cleanups.push(() => {
                    const s = window.Alpine.store('moduleTable');
                    if (s && s[instanceKey]) {
                        delete s[instanceKey];
                    }
                });
            }

            // ── 3. Filter slide-over: blur on close ──────────────────── //

            // The filter slide-over is Alpine-driven.
            // closeFilters() handles the blur; nothing extra needed here.
            // Kept as a comment block so the section numbering stays readable.

            // ── 4 & 5. Filter persistence ────────────────────────────── //

            if (this._persistenceEnabled) {
                this.persistEnabled = localStorage.getItem(this._tablePrefix + 'persistEnabled') === '1';

                // Restore saved filters only when the URL carries no filter
                // parameters — URL state always wins over localStorage.
                if (this.persistEnabled && Object.keys(this.$wire.filters).length === 0) {
                    const stored = localStorage.getItem(this._tablePrefix + 'persistedFilters');
                    if (stored) {
                        try {
                            const parsed = JSON.parse(stored);
                            if (parsed && typeof parsed === 'object' && Object.keys(parsed).length > 0) {
                                this.$wire.set('filters', parsed);
                                this.$wire.set('page', 1);
                            }
                        } catch (_e) {
                            // Corrupt localStorage entry — silently discard.
                        }
                    }
                }

                // Keep the persisted copy in sync whenever filters change.
                this.$wire.$watch('filters', (val) => { this.persistFilters(val); });
            }

            // ── 6. Bookmark migration + loading ─────────────────────── //

            if (this._bookmarkFiltersEnabled) {
                const oldKey = this._tablePrefix + 'savedFilters';
                const newKey = this._tablePrefix + 'bookmarkedFilters';

                // One-time migration from the legacy key name.
                const oldRaw = localStorage.getItem(oldKey);
                if (oldRaw && !localStorage.getItem(newKey)) {
                    localStorage.setItem(newKey, oldRaw);
                    localStorage.removeItem(oldKey);
                }

                const raw = localStorage.getItem(newKey);
                if (raw) {
                    try {
                        this.bookmarkedFilters = JSON.parse(raw) || [];
                    } catch (_e) {
                        this.bookmarkedFilters = [];
                    }
                }

                // Re-initialise the Lookup bookmark picker each time the
                // filter slide-over opens. architect:filters-opened replaces the
                // older panel-open event.
                document.addEventListener('architect:filters-opened', (e) => {
                    if (e.detail.instanceKey === this._instanceKey) {
                        this.initBookmarkedFiltersPicker();
                    }
                });

                // Also rebuild when the bookmark list itself changes (e.g.
                // after adding or deleting a bookmark) and the panel is open.
                this.$watch('bookmarkedFilters', () => {
                    // Keep the lookup map in sync regardless of panel visibility.
                    this._rebuildBookmarkLookup();
                    // Force the Lookup cache miss next time the panel opens.
                    this._bookmarkFilterHash = null;

                    // If the panel is currently open, re-init Lookup immediately.
                    if (this.filtersOpen) {
                        this.$nextTick(() => { this.initBookmarkedFiltersPicker(); });
                    }
                });

                // Initial population of the lookup map after bookmarks are
                // hydrated from localStorage above.
                this._rebuildBookmarkLookup();
            }

            // ── 7. Filters-cleared event ─────────────────────────────── //

            // When all filters are cleared via the header button, Livewire
            // dispatches a browser event. We imperatively reset every native
            // input inside the filter offcanvas because the panel lives inside
            // `wire:ignore` + `x-teleport`, which breaks Alpine's reactivity.
            Livewire.on('module-table:filters-cleared', ({ instanceKey }) => {
                if (instanceKey !== this._instanceKey) return;

                const panel = document.getElementById('arch-filter-panel-' + instanceKey);
                if (!panel) return;

                // Plain text and date/time inputs.
                panel.querySelectorAll(
                    'input[type="text"], input[type="date"], input[type="time"], input[type="datetime-local"]'
                ).forEach((el) => { el.value = ''; });

                // Radio buttons — re-check the "All" option (value = '').
                panel.querySelectorAll('input[type="radio"]').forEach((el) => {
                    el.checked = el.value === '';
                });

                // Native selects (Lookup removed — architectCombobox handles its own
                // clear via the architect:filters-opened / syncValue() path).
                panel.querySelectorAll('select').forEach((el) => {
                    if (el.multiple) {
                        Array.from(el.options).forEach((o) => { o.selected = false; });
                    } else {
                        el.value = '';
                    }
                });
            });

            // ── 8. Row-action browser events ─────────────────────────── //

            // Engine::handleRowAction() dispatches 'row-action:{key}' for
            // actions that have no URL and no panelBlade.  We listen here so
            // the component itself handles them without requiring a parent.
            //
            // row-action:audit — opens the Architect audit trail for the record.
            Livewire.on('row-action:audit', ({ id }) => {
                window.architectToast.info(
                    'Audit trail viewer is not yet available for this record.',
                    'Audit Trail',
                    { timeOut: 4000 }
                );
            });

            // row-action:view — opens the record detail view.  When the model
            // implements HasViewAll, Engine dispatches module-table:open-view
            // instead; this handler catches the fallback case.
            Livewire.on('row-action:view', ({ id }) => {
                window.architectToast.info(
                    'View panel is not yet available for this record.',
                    'View Record',
                    { timeOut: 3000 }
                );
            });

            // ── 9. Tooltip hook ─────────────────────────────────────── //
            ensureTooltipHook();

            // ── 9. Auto-refresh timer ──────────────────────────────────── //
            // Kick off the countdown tick only when the feature is enabled.
            // arRefresh() will restart the tick after each refresh so the
            // full interval is always counted from the last completion.
            if (this._arInterval) {
                this._restartArTick();
            }

            // ── 10. Supersearch hook cleanup ──────────────────────────── //
            // When this table's definition implements HasSupersearchHook, the
            // Livewire engine dispatches hook-mounted on server-side mount.
            // We register the matching hook-unmounted dispatch here using
            // Alpine's internal _x_cleanups queue, which is drained by
            // cleanupElement(el) when the element is removed from the DOM.
            // Note: $cleanup magic is not registered in this Alpine/Livewire
            // build, so we write to _x_cleanups directly.
            if (options.supersearchHookId) {
                const hookId = options.supersearchHookId;
                const el = this.$el;
                if (!el._x_cleanups) el._x_cleanups = [];
                el._x_cleanups.push(() => {
                    window.dispatchEvent(new CustomEvent('architect:supersearch:hook-unmounted', {
                        detail:  { componentId: hookId },
                        bubbles: true,
                    }));
                });
            }
        },
    }));
}
