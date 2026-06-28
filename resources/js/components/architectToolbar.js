/**
 * architectToolbar — Alpine.js data component for ToolbarEngine.
 *
 * Responsibilities:
 *  1. On init: read localStorage and URL query-params for persisted values
 *     and push them to Livewire once. URL params take priority over localStorage.
 *  2. Maintain Alpine store 'architectToolbar'[toolbarKey] with current state
 *     so sibling components can reactively read it.
 *  3. On architect:toolbar:*-changed events, write the new value to
 *     localStorage and/or update the URL query-param as configured.
 *  4. On browser popstate (back/forward), re-read URL params and push to
 *     $wire.loadFromUrl() so Livewire state stays in sync.
 *
 * @param {{ toolbarKey: string, persistKeys: Record<string, string>, urlPersistKeys: Record<string, string> }} config
 */
export function registerArchitectToolbar(Alpine) {
    Alpine.data('architectToolbar', ({ toolbarKey, persistKeys, urlPersistKeys }) => ({
        toolbarKey,
        persistKeys,
        urlPersistKeys,

        init() {
            // Ensure the global store namespace exists.
            if (!Alpine.store('architectToolbar')) {
                Alpine.store('architectToolbar', {});
            }
            Alpine.store('architectToolbar')[toolbarKey] = Alpine.store('architectToolbar')[toolbarKey] ?? {};

            // ── Helpers ───────────────────────────────────────────────────────

            /** Write or delete a URL query param via history.replaceState (no page reload). */
            const updateUrlParam = (name, value) => {
                const params = new URLSearchParams(location.search);
                const str    = value === '' || value === null || value === undefined ? null : String(value);
                if (str === null) {
                    params.delete(name);
                } else {
                    params.set(name, str);
                }
                const qs = params.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            };

            /** Read all URL-persisted values from the current query string. */
            const readUrlParams = () => {
                const urlState = {};
                const params   = new URLSearchParams(location.search);
                for (const [compoundKey, urlParam] of Object.entries(urlPersistKeys)) {
                    const raw = params.get(urlParam);
                    if (raw !== null) {
                        if (compoundKey.startsWith('toggle.') || compoundKey.startsWith('checkbox.')) {
                            urlState[compoundKey] = raw === 'true';
                        } else {
                            urlState[compoundKey] = raw;
                        }
                    }
                }
                return urlState;
            };

            // ── Init: push localStorage state first, then URL (URL wins) ─────

            const storedState = {};
            for (const [compoundKey, lsKey] of Object.entries(persistKeys)) {
                const raw = localStorage.getItem(lsKey);
                if (raw !== null) {
                    if (compoundKey.startsWith('toggle.') || compoundKey.startsWith('checkbox.')) {
                        storedState[compoundKey] = raw === 'true';
                    } else {
                        storedState[compoundKey] = raw;
                    }
                }
            }
            if (Object.keys(storedState).length > 0) {
                this.$wire.call('loadFromLocalStorage', storedState);
            }

            const urlState = readUrlParams();
            if (Object.keys(urlState).length > 0) {
                this.$wire.call('loadFromUrl', urlState);
            }

            // ── Browser back/forward: re-apply URL params ─────────────────────
            window.addEventListener('popstate', () => {
                this.$wire.call('loadFromUrl', readUrlParams());
            });

            // ── radio-changed (ToolbarRadioGroup + DropdownRadioGroup) ────────
            window.addEventListener('architect:toolbar:radio-changed', (e) => {
                if (e.detail?.toolbarKey !== toolbarKey) return;
                const itemKey = e.detail.itemKey;
                // itemKey may be plain key (ToolbarRadioGroup) or compound key
                // (DropdownRadioGroup). Check both prefix buckets.
                const lsKey = persistKeys['radio.' + itemKey]
                           ?? persistKeys['dropdown-radio.' + itemKey];
                if (lsKey) localStorage.setItem(lsKey, e.detail.value);
                const urlParam = urlPersistKeys['radio.' + itemKey]
                              ?? urlPersistKeys['dropdown-radio.' + itemKey];
                if (urlParam) updateUrlParam(urlParam, e.detail.value);
                Alpine.store('architectToolbar')[toolbarKey] = {
                    ...Alpine.store('architectToolbar')[toolbarKey],
                    radioValues: {
                        ...(Alpine.store('architectToolbar')[toolbarKey]?.radioValues ?? {}),
                        [itemKey]: e.detail.value,
                    },
                };
            });

            // ── toggle-changed ────────────────────────────────────────────────
            window.addEventListener('architect:toolbar:toggle-changed', (e) => {
                if (e.detail?.toolbarKey !== toolbarKey) return;
                const compoundKey = e.detail.dropdownKey
                    ? e.detail.dropdownKey + '.' + e.detail.itemKey
                    : e.detail.itemKey;
                const lsKey = persistKeys['toggle.' + compoundKey];
                if (lsKey) localStorage.setItem(lsKey, e.detail.value ? 'true' : 'false');
                const urlParam = urlPersistKeys['toggle.' + compoundKey];
                if (urlParam) updateUrlParam(urlParam, e.detail.value ? 'true' : 'false');
                Alpine.store('architectToolbar')[toolbarKey] = {
                    ...Alpine.store('architectToolbar')[toolbarKey],
                    toggleValues: {
                        ...(Alpine.store('architectToolbar')[toolbarKey]?.toggleValues ?? {}),
                        [compoundKey]: e.detail.value,
                    },
                };
            });

            // ── checkbox-changed ──────────────────────────────────────────────
            window.addEventListener('architect:toolbar:checkbox-changed', (e) => {
                if (e.detail?.toolbarKey !== toolbarKey) return;
                const compoundKey = e.detail.dropdownKey
                    ? e.detail.dropdownKey + '.' + e.detail.itemKey
                    : e.detail.itemKey;
                const lsKey = persistKeys['checkbox.' + compoundKey];
                if (lsKey) localStorage.setItem(lsKey, e.detail.value ? 'true' : 'false');
                const urlParam = urlPersistKeys['checkbox.' + compoundKey];
                if (urlParam) updateUrlParam(urlParam, e.detail.value ? 'true' : 'false');
                Alpine.store('architectToolbar')[toolbarKey] = {
                    ...Alpine.store('architectToolbar')[toolbarKey],
                    checkboxValues: {
                        ...(Alpine.store('architectToolbar')[toolbarKey]?.checkboxValues ?? {}),
                        [compoundKey]: e.detail.value,
                    },
                };
            });

            // ── text-changed ──────────────────────────────────────────────────
            window.addEventListener('architect:toolbar:text-changed', (e) => {
                if (e.detail?.toolbarKey !== toolbarKey) return;
                const compoundKey = e.detail.dropdownKey
                    ? e.detail.dropdownKey + '.' + e.detail.itemKey
                    : e.detail.itemKey;
                const lsKey = persistKeys['text.' + compoundKey];
                if (lsKey) localStorage.setItem(lsKey, e.detail.value);
                const urlParam = urlPersistKeys['text.' + compoundKey];
                if (urlParam) updateUrlParam(urlParam, e.detail.value);
                Alpine.store('architectToolbar')[toolbarKey] = {
                    ...Alpine.store('architectToolbar')[toolbarKey],
                    textValues: {
                        ...(Alpine.store('architectToolbar')[toolbarKey]?.textValues ?? {}),
                        [compoundKey]: e.detail.value,
                    },
                };
            });

            // ── search-changed ────────────────────────────────────────────────
            window.addEventListener('architect:toolbar:search-changed', (e) => {
                if (e.detail?.toolbarKey !== toolbarKey) return;
                const itemKey = e.detail.itemKey;
                const lsKey = persistKeys['search.' + itemKey];
                if (lsKey) localStorage.setItem(lsKey, e.detail.value ?? '');
                const urlParam = urlPersistKeys['search.' + itemKey];
                if (urlParam) updateUrlParam(urlParam, e.detail.value ?? '');
                Alpine.store('architectToolbar')[toolbarKey] = {
                    ...Alpine.store('architectToolbar')[toolbarKey],
                    searchValues: {
                        ...(Alpine.store('architectToolbar')[toolbarKey]?.searchValues ?? {}),
                        [itemKey]: e.detail.value,
                    },
                };
            });
        },
    }));
}

