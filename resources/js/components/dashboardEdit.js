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
         * Initialised from localStorage on init(), falls back to defaults.
         */
        sections: [],

        /** Named layout presets saved by the user. */
        presets: [],

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
            const saved = this._load();
            this.sections = sectionDefs.map(def => {
                const s = (saved.sections ?? []).find(ss => ss.key === def.key);
                return {
                    key:     def.key,
                    span:    s?.span    ?? def.defaultSpan ?? 12,
                    height:  s?.height  ?? null,
                    visible: s?.visible ?? true,
                };
            });
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
            this.sections = this.sections.map(s => {
                const ps = preset.sections.find(ps => ps.key === s.key);
                return ps ? { ...s, span: ps.span, height: ps.height, visible: ps.visible } : s;
            });
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
