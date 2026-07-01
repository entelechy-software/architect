/**
 * dashboardEdit — Alpine.js data component for the Stats Dashboard Engine.
 *
 * Manages per-dashboard layout state (section visibility, column spans,
 * min-heights, fullscreen mode) and named presets, all persisted to
 * localStorage under a dashboard-specific key.
 *
 * Registered as Alpine.data('dashboardEdit', ...) so the stats engine
 * blade can call:
 *   x-data="dashboardEdit({ dashboardKey, sections })"
 *
 * @param {object} Alpine - The Alpine.js global object.
 */
export function registerDashboardEdit(Alpine) {
    Alpine.data('dashboardEdit', ({ dashboardKey, sections: sectionDefs }) => ({

        // ── Reactive state ─────────────────────────────────────────────────

        /** Whether the layout-edit toolbar is visible. */
        editMode: false,

        /** Key of the section currently shown fullscreen, or null. */
        fullscreenKey: null,

        /** Name being typed for a new preset. */
        newPresetName: '',

        /**
         * Live section state: [{key, span, height, visible}, ...].
         * Order of this array controls visual order via CSS `order`.
         * Initialised from localStorage on init(), falls back to defaults.
         */
        sections: [],

        /** Named layout presets saved by the user. */
        presets: [],

        // ── Drag-to-reorder state ──────────────────────────────────────────

        /** Key of the section currently being dragged, or null. */
        dragKey: null,

        /** Key of the section the dragged item is hovering over, or null. */
        dragOverKey: null,

        /** True only when drag was initiated via the drag handle. */
        _canDrag: false,

        /** Timer reference for debouncing dragleave (avoids child-element flicker). */
        _dragLeaveTimer: null,

        // ── Computed getters ───────────────────────────────────────────────

        /** Keys of currently-visible sections (used by the export button). */
        get visibleKeys() {
            return this.sections.filter(s => s.visible).map(s => s.key);
        },

        /** Ordered sections list (alias so blade can iterate sortedSections). */
        get sortedSections() {
            return this.sections;
        },

        /** True when the user has hidden every section. */
        get allHidden() {
            return this.sections.length > 0 && this.sections.every(s => !s.visible);
        },

        // ── Lifecycle ──────────────────────────────────────────────────────

        init() {
            const saved     = this._load();
            const savedSecs = saved.sections ?? [];

            if (savedSecs.length > 0) {
                // Restore sections in their saved (possibly reordered) sequence.
                const savedKeys = savedSecs.map(ss => ss.key);
                this.sections = [
                    ...savedSecs
                        .filter(ss => sectionDefs.some(def => def.key === ss.key))
                        .map(ss => {
                            const def = sectionDefs.find(d => d.key === ss.key);
                            return {
                                key:     ss.key,
                                span:    ss.span    ?? def?.defaultSpan ?? 12,
                                height:  ss.height  ?? null,
                                visible: ss.visible ?? true,
                            };
                        }),
                    // Any sections added to the definition after the last save.
                    ...sectionDefs
                        .filter(def => !savedKeys.includes(def.key))
                        .map(def => ({ key: def.key, span: def.defaultSpan ?? 12, height: null, visible: true })),
                ];
            } else {
                this.sections = sectionDefs.map(def => ({
                    key:     def.key,
                    span:    def.defaultSpan ?? 12,
                    height:  null,
                    visible: true,
                }));
            }
            this.presets = saved.presets ?? [];
        },

        // ── Edit mode ──────────────────────────────────────────────────────

        toggleEdit() {
            this.editMode = !this.editMode;
        },

        // ── Visibility ─────────────────────────────────────────────────────

        isVisible(key) {
            return this.sections.find(s => s.key === key)?.visible ?? true;
        },

        toggleVisible(key) {
            const s = this.sections.find(s => s.key === key);
            if (!s) return;
            if (s.visible && this.visibleKeys.length <= 1) return; // keep ≥1 visible
            s.visible = !s.visible;
            this._save();
        },

        // ── Column span ────────────────────────────────────────────────────

        getSpan(key) {
            return this.sections.find(s => s.key === key)?.span ?? 12;
        },

        getSpanLabel(key) {
            return this.getSpan(key) + '/12';
        },

        stepSpan(key, delta) {
            const s = this.sections.find(s => s.key === key);
            if (!s) return;
            s.span = Math.min(12, Math.max(1, s.span + delta));
            this._save();
        },

        atSpanMin(key) {
            return this.getSpan(key) <= 1;
        },

        atSpanMax(key) {
            return this.getSpan(key) >= 12;
        },

        // ── Min-height ─────────────────────────────────────────────────────

        /** Returns the CSS minHeight string (e.g. '350px') or null for auto. */
        getMinHeight(key) {
            return this.sections.find(s => s.key === key)?.height ?? null;
        },

        getHeightLabel(key) {
            const h = this.getMinHeight(key);
            return h ? h : 'auto';
        },

        stepHeight(key, delta) {
            const s = this.sections.find(s => s.key === key);
            if (!s) return;
            const current = parseInt(s.height) || 300;
            s.height = Math.min(1200, Math.max(100, current + delta * 50)) + 'px';
            this._save();
        },

        atHeightMin(key) {
            return (parseInt(this.getMinHeight(key)) || 300) <= 100;
        },

        atHeightMax(key) {
            return (parseInt(this.getMinHeight(key)) || 300) >= 1200;
        },

        // ── Fullscreen ─────────────────────────────────────────────────────

        setFullscreen(key) {
            this.fullscreenKey = key;
        },

        closeFullscreen() {
            this.fullscreenKey = null;
        },

        // ── Presets ────────────────────────────────────────────────────────

        savePreset() {
            const name = this.newPresetName.trim();
            if (!name) return;
            this.presets = this.presets.filter(p => p.name !== name);
            this.presets.push({
                name,
                sections: this.sections.map(({ key, span, height, visible }) => ({ key, span, height, visible })),
            });
            this.newPresetName = '';
            this._save();
        },

        loadPreset(name) {
            const preset = this.presets.find(p => p.name === name);
            if (!preset) return;
            const presetKeys = preset.sections.map(ps => ps.key);
            this.sections = [
                // Restore preset order and settings.
                ...preset.sections
                    .filter(ps => this.sections.some(s => s.key === ps.key))
                    .map(ps => {
                        const s = this.sections.find(s => s.key === ps.key);
                        return { ...s, span: ps.span, height: ps.height, visible: ps.visible };
                    }),
                // Any sections not in the preset (appended at end).
                ...this.sections.filter(s => !presetKeys.includes(s.key)),
            ];
            this._save();
        },

        deletePreset(name) {
            this.presets = this.presets.filter(p => p.name !== name);
            this._save();
        },

        // ── Reset ──────────────────────────────────────────────────────────

        reset() {
            this.sections = sectionDefs.map(def => ({
                key:     def.key,
                span:    def.defaultSpan ?? 12,
                height:  null,
                visible: true,
            }));
            this._save();
        },

        // ── Drag-to-reorder ────────────────────────────────────────────────

        /** Called on mousedown of the drag handle; arms the drag. */
        handleMouseDown() {
            this._canDrag = true;
            window.addEventListener('mouseup', () => { this._canDrag = false; }, { once: true });
        },

        /** Fires on the section's dragstart — only allows if initiated via handle. */
        sectionDragStart(key, event) {
            if (!this._canDrag) { event.preventDefault(); return; }
            this.dragKey = key;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', key);
        },

        /** Cleans up all drag state. */
        sectionDragEnd() {
            this.dragKey     = null;
            this.dragOverKey = null;
            this._canDrag    = false;
            clearTimeout(this._dragLeaveTimer);
        },

        /** Marks this section as the drop target. */
        sectionDragOver(key, event) {
            if (!this.dragKey || this.dragKey === key) return;
            event.dataTransfer.dropEffect = 'move';
            clearTimeout(this._dragLeaveTimer);
            this.dragOverKey = key;
        },

        /** Debounced clear of the drop target (prevents child-element flicker). */
        sectionDragLeave(key) {
            if (this.dragOverKey !== key) return;
            this._dragLeaveTimer = setTimeout(() => {
                if (this.dragOverKey === key) this.dragOverKey = null;
            }, 50);
        },

        /** Reorders sections array and persists. */
        sectionDrop(key) {
            const fromKey = this.dragKey;
            if (!fromKey || fromKey === key) { this.sectionDragEnd(); return; }
            const fromIdx = this.sections.findIndex(s => s.key === fromKey);
            const toIdx   = this.sections.findIndex(s => s.key === key);
            if (fromIdx !== -1 && toIdx !== -1) {
                const [moved] = this.sections.splice(fromIdx, 1);
                this.sections.splice(toIdx, 0, moved);
                this._save();
            }
            this.sectionDragEnd();
        },

        /** CSS order index for a section (drives visual reorder without DOM changes). */
        getSectionOrder(key) {
            const idx = this.sections.findIndex(s => s.key === key);
            return idx === -1 ? 0 : idx;
        },

        // ── Persistence helpers ────────────────────────────────────────────

        _storageKey() {
            return `architect_dashboard_${dashboardKey}`;
        },

        _load() {
            try {
                return JSON.parse(localStorage.getItem(this._storageKey()) ?? '{}');
            } catch {
                return {};
            }
        },

        _save() {
            try {
                localStorage.setItem(this._storageKey(), JSON.stringify({
                    sections: this.sections.map(({ key, span, height, visible }) => ({ key, span, height, visible })),
                    presets:  this.presets,
                }));
            } catch {
                // localStorage may be unavailable (private browsing, quota exceeded)
            }
        },
    }));
}
