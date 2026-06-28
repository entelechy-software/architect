/**
 * architectForms — Alpine.js components backing the interactive Forms
 * field views added in Phase 4 (resources/views/forms/fields/*.blade.php).
 *
 * Each export is registered as an Alpine.data() factory and instantiated
 * via x-data="architectXyz({...})" in the matching Blade view.
 */

/**
 * Drag state for the FileUpload dropzone. The actual upload is handled by
 * Livewire's native wire:model file binding; this only tracks the
 * dragover/dragleave visual state (toggled via @dragover/@dragleave in
 * the Blade view through the `dragging` property).
 */
function architectFileUpload() {
    return {
        dragging: false,
    };
}

/**
 * AJAX single/multi-select combobox backing LookupField.
 *
 * @param {Object}  options
 * @param {string}  options.url        Source endpoint — receives ?q= for search.
 * @param {boolean} options.multi      Whether multiple selections are allowed.
 * @param {string}  options.wireField  Livewire property path, e.g. "formData.member_id".
 */
function architectCombobox({ url, multi = false, wireField }) {
    return {
        open: false,
        query: '',
        options: [],
        loading: false,
        selected: multi ? [] : null,
        selectedLabel: '',
        _debounce: null,

        get hasValue() {
            return multi ? this.selected.length > 0 : this.selected !== null;
        },

        toggleDropdown() {
            this.open = !this.open;
            if (this.open) {
                this.search('');
                this.$nextTick(() => this.$refs.search?.focus());
            }
        },

        closeDropdown() {
            this.open = false;
        },

        onQueryInput() {
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => this.search(this.query), 250);
        },

        async search(term) {
            if (!url) return;
            this.loading = true;
            try {
                const response = await fetch(`${url}?q=${encodeURIComponent(term)}`);
                const data = await response.json();
                this.options = Array.isArray(data) ? data : (data.results ?? []);
            } catch {
                this.options = [];
            } finally {
                this.loading = false;
            }
        },

        isSelected(id) {
            return multi ? this.selected.includes(id) : this.selected === id;
        },

        select(id, text) {
            if (id === null) {
                this.clear();
                return;
            }

            if (multi) {
                if (!this.selected.includes(id)) this.selected.push(id);
            } else {
                this.selected = id;
                this.selectedLabel = text;
                this.open = false;
            }

            this.$wire.set(wireField, multi ? this.selected : { val: id, txt: text });
        },

        clear() {
            this.selected = multi ? [] : null;
            this.selectedLabel = '';
            this.$wire.set(wireField, multi ? [] : null);
        },
    };
}

/**
 * Backs Repeater and Builder — an ordered, addable/removable list of rows.
 * Row sub-fields are plain objects synced back to Livewire as a whole array
 * (see resources/views/forms/fields/repeater.blade.php for the known
 * limitation around nested arbitrary field types).
 *
 * @param {Object}       options
 * @param {string}       options.wireField  Livewire property path.
 * @param {number|null}  options.minItems
 * @param {number|null}  options.maxItems
 */
function architectRepeater({ wireField, minItems = null, maxItems = null }) {
    return {
        items: [],

        init() {
            this.items = this.$wire.get(wireField) ?? [];
            this.$watch('items', (value) => this.$wire.set(wireField, value));
        },

        add(row = {}) {
            if (maxItems !== null && this.items.length >= maxItems) return;
            this.items.push(row);
        },

        remove(index) {
            if (minItems !== null && this.items.length <= minItems) return;
            this.items.splice(index, 1);
        },
    };
}

/**
 * Backs TagsInput — free-text tags with optional autocomplete suggestions.
 *
 * @param {Object}              options
 * @param {string}              options.wireField
 * @param {Array<string>}       options.suggestions
 * @param {boolean}             options.allowCreate
 */
function architectTagsInput({ wireField, suggestions = [], allowCreate = true }) {
    return {
        tags: [],
        query: '',
        suggestions,

        init() {
            this.tags = this.$wire.get(wireField) ?? [];
            this.$watch('tags', (value) => this.$wire.set(wireField, value));
        },

        addFromQuery() {
            const value = this.query.trim();
            if (value === '') return;
            if (!allowCreate && !this.suggestions.includes(value)) return;
            if (!this.tags.includes(value)) this.tags.push(value);
            this.query = '';
        },

        removeTag(index) {
            this.tags.splice(index, 1);
        },
    };
}

/**
 * Backs KeyValue — repeatable {key, value} rows.
 *
 * @param {Object} options
 * @param {string} options.wireField
 */
function architectKeyValue({ wireField }) {
    return {
        rows: [],

        init() {
            const initial = this.$wire.get(wireField) ?? {};
            this.rows = Object.entries(initial).map(([key, value]) => ({ key, value }));
            this.$watch('rows', (value) => {
                const obj = {};
                value.forEach(({ key, value: v }) => { if (key !== '') obj[key] = v; });
                this.$wire.set(wireField, obj);
            });
        },

        add() {
            this.rows.push({ key: '', value: '' });
        },

        remove(index) {
            this.rows.splice(index, 1);
        },
    };
}

/**
 * Backs ColorPicker — keeps a hex/rgb/hsl string in sync with Livewire.
 *
 * @param {Object} options
 * @param {string} options.wireField
 * @param {string} options.format
 * @param {boolean} options.withAlpha
 */
function architectColorPicker({ wireField, format = 'hex', withAlpha = false }) {
    return {
        open: false,
        format,
        withAlpha,
        value: '',

        init() {
            this.value = this.$wire.get(wireField) ?? (format === 'hex' ? '#0ea5e9' : '');
            this.$watch('value', (v) => this.$wire.set(wireField, v));
        },
    };
}

/**
 * Backs RichEditor — loads TipTap from CDN (unpkg) on first use and keeps
 * its HTML content synced into the bound Livewire property. wire:ignore on
 * the root element (see rich-editor.blade.php) prevents Livewire from
 * touching the DOM TipTap manages.
 *
 * @param {Object} options
 * @param {string} options.wireField
 * @param {Array<string>} options.toolbar
 */
function architectRichEditor({ wireField, toolbar = [] }) {
    return {
        editor: null,
        toolbar,

        async init() {
            const value = this.$wire.get(wireField) ?? '';
            const { Editor } = await import('https://esm.sh/@tiptap/core@2');
            const { default: StarterKit } = await import('https://esm.sh/@tiptap/starter-kit@2');

            this.editor = new Editor({
                element: this.$refs.editor,
                extensions: [StarterKit],
                content: value,
                onUpdate: ({ editor }) => this.$wire.set(wireField, editor.getHTML()),
            });
        },
    };
}

/**
 * Backs CodeEditor — loads Monaco from CDN (jsdelivr) on first use and
 * keeps its text content synced into the bound Livewire property.
 *
 * @param {Object} options
 * @param {string} options.wireField
 * @param {string} options.language
 * @param {string} options.theme
 */
function architectCodeEditor({ wireField, language = 'plaintext', theme = 'vs-dark' }) {
    return {
        editor: null,

        init() {
            const value = this.$wire.get(wireField) ?? '';
            const el = this.$refs.editor;

            const boot = () => {
                window.require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@latest/min/vs' } });
                window.require(['vs/editor/editor.main'], () => {
                    this.editor = window.monaco.editor.create(el, { value, language, theme, automaticLayout: true });
                    this.editor.onDidChangeModelContent(() => {
                        this.$wire.set(wireField, this.editor.getValue());
                    });
                });
            };

            if (window.monaco) {
                boot();
                return;
            }

            if (!window.require) {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/monaco-editor@latest/min/vs/loader.js';
                script.onload = boot;
                document.head.appendChild(script);
            } else {
                boot();
            }
        },
    };
}

export function registerArchitectForms(Alpine) {
    Alpine.data('architectFileUpload', architectFileUpload);
    Alpine.data('architectCombobox', architectCombobox);
    Alpine.data('architectRepeater', architectRepeater);
    Alpine.data('architectTagsInput', architectTagsInput);
    Alpine.data('architectKeyValue', architectKeyValue);
    Alpine.data('architectColorPicker', architectColorPicker);
    Alpine.data('architectRichEditor', architectRichEditor);
    Alpine.data('architectCodeEditor', architectCodeEditor);
}
