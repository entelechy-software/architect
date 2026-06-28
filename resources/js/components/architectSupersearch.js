/**
 * architectSupersearch — Alpine.js data component for SupersearchEngine.
 *
 * Responsibilities:
 *  1. Open/close the search overlay (keyboard shortcut, click-outside, Esc)
 *  2. Debounce input and call $wire.search(query)
 *  3. Keyboard navigation through results (↑↓ arrows, Enter)
 *  4. Handle client-side actions (copy, email, phone, wire)
 *  5. Persist recent searches to localStorage
 *  6. Listen for architect:supersearch:run-recent events from the recent pane
 *
 * @param {{ key: string, shortcut: string, placeholder: string }} config
 */
export function registerArchitectSupersearch(Alpine) {
    Alpine.data('architectSupersearch', ({ key, shortcut, placeholder }) => ({
        key,
        shortcut,
        placeholder,

        open: false,
        query: '',
        activeIndex: -1,
        copiedToast: false,
        debounceTimer: null,
        copiedTimer: null,
        /** Flat count of result items, synced after results update */
        totalItems: 0,

        init() {
            // ── Keyboard shortcut ────────────────────────────────────────────
            const [modifier, key_] = this._parseShortcut(this.shortcut);
            window.addEventListener('keydown', (e) => {
                const modOk = modifier === 'cmd' ? (e.metaKey || e.ctrlKey) : e.ctrlKey;
                if (modOk && e.key.toLowerCase() === key_) {
                    e.preventDefault();
                    this.openOverlay();
                }
            });

            // ── Result row events (plain onclick/onmouseenter fire these) ────
            // Result rows are morphed in by Livewire so Alpine directives on them
            // are never initialized. Plain HTML event attributes dispatch window
            // events instead; we catch them here.
            window.addEventListener('ss:click', (e) => {
                const { g, i, a } = e.detail;
                console.log('[SS] ss:click received', g, i, a);
                this.handleResultClick(g, i, a);
            });
            window.addEventListener('ss:hover', (e) => {
                this._setActiveIndex(e.detail.idx);
            });

            // ── Run a recent search when the recent pane emits ───────────────
            this.$el.addEventListener('architect:supersearch:run-recent', (e) => {
                this.query = e.detail.term;
                this.$refs.input?.focus();
                this.triggerSearch();
            });

            // ── Livewire updates results: recount flat items ─────────────────
            this.$watch('$wire.results', (results) => {
                this.totalItems = (results || []).reduce((n, g) => n + g.items.length, 0);
                // Keep activeIndex in bounds after search refresh
                if (this.activeIndex >= this.totalItems) {
                    this._setActiveIndex(this.totalItems > 0 ? 0 : -1);
                }
            });
        },

        openOverlay() {
            this.open = true;
            this.$wire.openOverlay();
            this.$nextTick(() => this.$refs.input?.focus());
        },

        closeOverlay() {
            this.open = false;
            this.query = '';
            this._setActiveIndex(-1);
            this.$wire.closeOverlay();
        },

        onInput() {
            clearTimeout(this.debounceTimer);
            this._setActiveIndex(-1);

            if (this.query.trim().length < 2) {
                this.$wire.set('results', []);
                return;
            }

            this.debounceTimer = setTimeout(() => this.triggerSearch(), 300);
        },

        triggerSearch() {
            if (this.query.trim().length >= 2) {
                this.$wire.search(this.query.trim());
            }
        },

        navigateUp() {
            if (this.totalItems === 0) return;
            this._setActiveIndex(this.activeIndex <= 0
                ? this.totalItems - 1
                : this.activeIndex - 1);
            this._scrollActiveIntoView();
        },

        navigateDown() {
            if (this.totalItems === 0) return;
            this._setActiveIndex(this.activeIndex >= this.totalItems - 1
                ? 0
                : this.activeIndex + 1);
            this._scrollActiveIntoView();
        },

        selectActive() {
            if (this.activeIndex < 0 || this.totalItems === 0) return;

            // Resolve groupIndex + itemIndex from flat activeIndex
            const results = this.$wire.results || [];
            let flat = 0;
            for (let gi = 0; gi < results.length; gi++) {
                const group = results[gi];
                for (let ii = 0; ii < group.items.length; ii++) {
                    if (flat === this.activeIndex) {
                        const item = group.items[ii];
                        this.handleResultClick(gi, ii, item.action || {});
                        return;
                    }
                    flat++;
                }
            }
        },

        /**
         * Handle a result click. Most actions are resolved fully client-side
         * from the action data already embedded in the result row by the server.
         * Only panel and wire actions require a Livewire round-trip.
         */
        handleResultClick(groupIndex, itemIndex, action) {
            const type = action?.type;
            console.log('[SS] handleResultClick type=', type, 'action=', action);

            // Save this query to recent searches
            if (this.query.trim().length >= 2) {
                this._saveRecent(this.query.trim());
            }

            switch (type) {
                case 'href':
                    this.closeOverlay();
                    if (action.newTab) {
                        window.open(action.url, '_blank', 'noopener');
                    } else {
                        window.location.href = action.url;
                    }
                    break;

                case 'open-tab':
                    document.dispatchEvent(new CustomEvent('architect:open-record', {
                        detail:  { type: action.tabType, props: action.props || {}, fallback: action.fallbackUrl || '' },
                        bubbles: true,
                    }));
                    this.closeOverlay();
                    break;

                case 'dispatch':
                    window.dispatchEvent(new CustomEvent(action.event, {
                        detail:  action.payload || {},
                        bubbles: true,
                    }));
                    this.closeOverlay();
                    break;

                case 'copy':
                    this._copyToClipboard(action.value || '');
                    this.closeOverlay();
                    break;

                case 'email':
                    window.location.href = 'mailto:' + encodeURIComponent(action.value || '');
                    this.closeOverlay();
                    break;

                case 'phone':
                    window.location.href = 'tel:' + (action.value || '').replace(/\s/g, '');
                    this.closeOverlay();
                    break;

                case 'download':
                    this.closeOverlay();
                    window.location.href = action.url;
                    break;

                case 'panel':
                case 'wire':
                    // These require server involvement
                    this.$wire.selectResult(groupIndex, itemIndex);
                    this.closeOverlay();
                    break;

                default:
                    // Unknown action — try server-side fallback
                    if (groupIndex !== null) {
                        this.$wire.selectResult(groupIndex, itemIndex);
                    }
                    this.closeOverlay();
                    break;
            }
        },

        // ── Active-index DOM helper ────────────────────────────────────────────
        /**
         * Updates activeIndex and syncs the highlight class on result rows.
         * Result rows use data-ss-flat and plain HTML events (no Alpine directives)
         * because Livewire morphs them in after Alpine's init tree walk.
         */
        _setActiveIndex(idx) {
            // Remove highlight from old
            if (this.activeIndex >= 0) {
                const prev = this.$el.querySelector(`[data-ss-flat="${this.activeIndex}"]`);
                if (prev) {
                    prev.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                }
            }
            this.activeIndex = idx;
            // Add highlight to new
            if (idx >= 0) {
                const next = this.$el.querySelector(`[data-ss-flat="${idx}"]`);
                if (next) {
                    next.classList.add('bg-blue-50', 'dark:bg-blue-900/20');
                }
            }
        },

        // ── localStorage helpers ─────────────────────────────────────────────

        _recentKey() {
            return `architectSupersearch_recent_${this.key}`;
        },

        getRecent() {
            try {
                return JSON.parse(localStorage.getItem(this._recentKey()) || '[]');
            } catch {
                return [];
            }
        },

        removeRecent(term) {
            const items = this.getRecent().filter(t => t !== term);
            localStorage.setItem(this._recentKey(), JSON.stringify(items));
        },

        _saveRecent(term) {
            const items = this.getRecent().filter(t => t !== term);
            items.unshift(term);
            localStorage.setItem(this._recentKey(), JSON.stringify(items.slice(0, 5)));
        },

        // ── Clipboard ────────────────────────────────────────────────────────

        _copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => this._showCopiedToast());
            } else {
                // Fallback for older browsers
                const el = document.createElement('textarea');
                el.value = text;
                el.style.position = 'fixed';
                el.style.opacity = '0';
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                this._showCopiedToast();
            }
        },

        _showCopiedToast() {
            clearTimeout(this.copiedTimer);
            this.copiedToast = true;
            this.copiedTimer = setTimeout(() => { this.copiedToast = false; }, 2000);
        },

        // ── Shortcut parsing ─────────────────────────────────────────────────

        _parseShortcut(shortcut) {
            // e.g. 'cmd+k' → ['cmd', 'k'], 'ctrl+/' → ['ctrl', '/']
            const parts = shortcut.toLowerCase().split('+');
            const modifier = parts[0] || 'cmd';
            const key_ = parts[1] || 'k';
            return [modifier, key_];
        },

        // ── Scroll helper ────────────────────────────────────────────────────

        _scrollActiveIntoView() {
            this.$nextTick(() => {
                const list = this.$refs.resultsList;
                if (!list) return;
                const active = list.querySelector('[aria-selected="true"]');
                active?.scrollIntoView({ block: 'nearest' });
            });
        },
    }));
}
