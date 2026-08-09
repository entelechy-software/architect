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

/**
 * Backs PasswordStrengthField — live client-side strength scoring (length
 * + character-class variety, no third-party lib per
 * ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 1's dependency policy). The
 * score/meter are purely a UX aid; the real enforcement is server-side
 * (see PasswordStrengthField::getRules()).
 *
 * @param {Object} options
 * @param {string} options.wireField
 * @param {number} options.minLength
 */
function architectPasswordStrength({ wireField, minLength = 12 }) {
    return {
        score: 0,
        label: '',

        init() {
            const value = this.$wire.get(wireField) ?? '';
            this.$refs.input.value = value;
            this.evaluate(value);
        },

        onInput(value) {
            this.$wire.set(wireField, value);
            this.evaluate(value);
        },

        evaluate(value) {
            const v = value ?? '';
            let score = 0;
            if (v.length >= minLength) score++;
            if (v.length >= minLength + 4) score++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            this.score = Math.min(score, 4);
            const labels = ['Very weak', 'Weak', 'Fair', 'Strong', 'Very strong'];
            this.label = v === '' ? '' : labels[this.score];

            const colors = ['#dc2626', '#d97706', '#eab308', '#16a34a', '#0ea5e9'];
            const meter = this.$refs.meter;
            meter.style.setProperty('--arch-password-strength-width', v === '' ? '0%' : `${((this.score + 1) / 5) * 100}%`);
            meter.style.setProperty('--arch-password-strength-color', v === '' ? 'transparent' : colors[this.score]);
            meter.dataset.label = this.label;
        },
    };
}

/**
 * Shared native-drag-and-drop reorder logic backing both SortableListField
 * and RankingField — functionally identical (see RankingField's own
 * docblock), kept as two Alpine.data registrations purely so each field's
 * Blade view can use its own semantic component name.
 *
 * Deliberately uses the browser's native HTML5 drag-and-drop events rather
 * than a third-party library (Wave 1 dependency policy: no third-party
 * libs — Sortable.js is reserved for Wave 2's KanbanBoardField, which
 * needs cross-column dragging this simple single-list case doesn't).
 *
 * @param {Object}               options
 * @param {string}               options.wireField
 * @param {Object<string,string>} options.options  key => label pairs.
 */
function architectOrderableList({ wireField, options = {} }) {
    return {
        order: [],
        dragIndex: null,

        init() {
            const existing = this.$wire.get(wireField);
            const keys = Object.keys(options);
            this.order = Array.isArray(existing) && existing.length
                ? existing.filter((k) => keys.includes(String(k)))
                : [...keys];
            keys.forEach((k) => { if (!this.order.includes(k)) this.order.push(k); });
        },

        label(key) {
            return options[key] ?? key;
        },

        onDragStart(index, event) {
            this.dragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },

        onDragOver(event) {
            event.preventDefault();
        },

        onDrop(index) {
            if (this.dragIndex === null || this.dragIndex === index) return;
            const [moved] = this.order.splice(this.dragIndex, 1);
            this.order.splice(index, 0, moved);
            this.dragIndex = null;
            this.$wire.set(wireField, this.order);
        },
    };
}

/**
 * Backs HierarchicalCheckboxTreeField — tri-state (indeterminate) checkbox
 * tree built with plain DOM APIs (no third-party lib, per Wave 1's
 * dependency policy). Selecting/deselecting a node cascades to every
 * descendant; a parent shows as indeterminate when only some descendants
 * are selected. The committed value is the flat array of every fully
 * selected node's key (branch or leaf), matching the field's docblock.
 *
 * @param {Object} options
 * @param {string} options.wireField
 * @param {Array<Object>} options.tree
 */
function architectCheckboxTree({ wireField, tree = [] }) {
    return {
        selected: [],

        init() {
            this.selected = this.$wire.get(wireField) ?? [];
            this.renderTree();
        },

        descendantKeys(node) {
            const keys = [node.key];
            (node.children ?? []).forEach((child) => keys.push(...this.descendantKeys(child)));
            return keys;
        },

        toggle(node, checked) {
            const keys = this.descendantKeys(node);
            if (checked) {
                keys.forEach((k) => { if (!this.selected.includes(k)) this.selected.push(k); });
            } else {
                this.selected = this.selected.filter((k) => !keys.includes(k));
            }
            this.$wire.set(wireField, this.selected);
            this.renderTree();
        },

        renderTree() {
            const container = this.$refs.tree;
            container.innerHTML = '';
            tree.forEach((node) => container.appendChild(this.buildNode(node)));
        },

        buildNode(node) {
            const wrapper = document.createElement('div');
            wrapper.className = 'arch-checkbox-tree__node';

            const label = document.createElement('label');
            label.className = 'arch-checkbox-tree__label';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            const keys = this.descendantKeys(node);
            const selectedCount = keys.filter((k) => this.selected.includes(k)).length;
            checkbox.checked = selectedCount === keys.length;
            checkbox.indeterminate = selectedCount > 0 && selectedCount < keys.length;
            checkbox.addEventListener('change', (e) => this.toggle(node, e.target.checked));

            const text = document.createElement('span');
            text.textContent = node.label;

            label.append(checkbox, text);
            wrapper.append(label);

            if (node.children?.length) {
                const children = document.createElement('div');
                children.className = 'arch-checkbox-tree__children';
                node.children.forEach((child) => children.appendChild(this.buildNode(child)));
                wrapper.append(children);
            }

            return wrapper;
        },
    };
}

/**
 * Backs KeyboardShortcutRecorderField — listens for the next key
 * combination pressed while the display input is focused and normalizes
 * it into a "cmd+shift+k"-style string.
 *
 * @param {Object} options
 * @param {string} options.wireField
 */
function architectKeyboardShortcutRecorder({ wireField }) {
    return {
        init() {
            this.$refs.display.value = this.$wire.get(wireField) ?? '';
        },

        record(event) {
            event.preventDefault();

            const key = event.key.toLowerCase();
            if (['control', 'meta', 'alt', 'shift'].includes(key)) {
                return;
            }

            const parts = [];
            if (event.metaKey) parts.push('cmd');
            if (event.ctrlKey) parts.push('ctrl');
            if (event.altKey) parts.push('alt');
            if (event.shiftKey) parts.push('shift');
            parts.push(key);

            const combo = parts.join('+');
            this.$refs.display.value = combo;
            this.$wire.set(wireField, combo);
        },
    };
}

/**
 * Backs DialKnobField — a draggable rotational control (pointer drag maps
 * to a 270° sweep between min/max), built with plain DOM/pointer-event
 * APIs (no third-party lib, per Wave 1's dependency policy).
 *
 * @param {Object} options
 * @param {string} options.wireField
 * @param {number} options.min
 * @param {number} options.max
 */
function architectDialKnob({ wireField, min = 0, max = 100 }) {
    return {
        value: 0,
        angle: 0,

        init() {
            this.value = this.$wire.get(wireField) ?? min;
            this.angle = this.angleFor(this.value);

            const container = this.$refs.dial;
            container.classList.add('arch-dial-knob__face');

            this.pointerEl = document.createElement('div');
            this.pointerEl.className = 'arch-dial-knob__pointer';

            this.readoutEl = document.createElement('span');
            this.readoutEl.className = 'arch-dial-knob__value';

            container.append(this.pointerEl, this.readoutEl);
            this.paint();

            container.addEventListener('pointerdown', (event) => this.startDrag(event, container));
        },

        angleFor(value) {
            const ratio = (value - min) / (max - min);
            return ratio * 270 - 135;
        },

        paint() {
            this.pointerEl.style.transform = `rotate(${this.angle}deg)`;
            this.readoutEl.textContent = Math.round(this.value * 100) / 100;
        },

        startDrag(event, container) {
            const rect = container.getBoundingClientRect();
            const center = { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };

            const onMove = (moveEvent) => {
                const dx = moveEvent.clientX - center.x;
                const dy = moveEvent.clientY - center.y;
                let deg = Math.atan2(dy, dx) * (180 / Math.PI) + 90;
                if (deg > 180) deg -= 360;
                deg = Math.max(-135, Math.min(135, deg));

                this.angle = deg;
                this.value = min + ((deg + 135) / 270) * (max - min);
                this.paint();
                this.$wire.set(wireField, this.value);
            };

            const onUp = () => {
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
            };

            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onUp);
        },
    };
}

/**
 * Backs MaskedInputField — formats input as the user types against a
 * simple placeholder-token mask (`9` = digit, `A` = letter, `*` =
 * alphanumeric, any other character is a literal), inserted automatically.
 * Plain vanilla JS per Wave 1's no-third-party-libs dependency policy.
 *
 * @param {Object}      options
 * @param {string}      options.wireField
 * @param {string|null} options.mask
 */
function architectMaskedInput({ wireField, mask = null }) {
    return {
        init() {
            const value = this.$wire.get(wireField) ?? '';
            this.$el.value = mask ? this.applyMask(value) : value;
        },

        onInput(event) {
            const masked = mask ? this.applyMask(event.target.value) : event.target.value;
            event.target.value = masked;
            this.$wire.set(wireField, masked);
        },

        applyMask(value) {
            const input = String(value ?? '').replace(/[^A-Za-z0-9]/g, '');
            let result = '';
            let i = 0;

            for (let m = 0; m < mask.length && i < input.length; m++) {
                const token = mask[m];
                const matches = (char) => (token === '9' && /\d/.test(char))
                    || (token === 'A' && /[A-Za-z]/.test(char))
                    || (token === '*');

                if (token === '9' || token === 'A' || token === '*') {
                    while (i < input.length && !matches(input[i])) i++;
                    if (i < input.length) {
                        result += input[i];
                        i++;
                    }
                } else {
                    result += token;
                }
            }

            return result;
        },
    };
}

/**
 * Backs CardInputField — a generic, vendor-agnostic adapter rather than a
 * hard-coded payment provider integration (ARCHITECT_IMPROVEMENT_PLAN.md
 * Phase 1 Wave 1). Loads providerScriptUrl as a <script> tag, then expects
 * that script to expose a `window.ArchitectCardProvider.mount(el, options,
 * onToken)` contract: `el` is the mount point, `options` carries the
 * publishable key, and `onToken` is called by the provider's SDK with the
 * resulting token/reference string once the shopper submits card details.
 * Never touches raw card data (PAN/CVC) — only the provider's returned
 * token ever reaches `$wire`.
 */
function architectCardInput({ wireField, providerScriptUrl = null, publishableKey = null }) {
    return {
        status: '',

        init() {
            if (!providerScriptUrl) {
                this.status = 'Card input has no providerScriptUrl configured.';

                return;
            }

            const existing = document.querySelector(`script[src="${providerScriptUrl}"]`);

            if (existing) {
                existing.dataset.loaded === 'true' ? this.mount() : existing.addEventListener('load', () => this.mount());

                return;
            }

            const script = document.createElement('script');
            script.src = providerScriptUrl;
            script.onload = () => {
                script.dataset.loaded = 'true';
                this.mount();
            };
            script.onerror = () => {
                this.status = 'Failed to load the payment provider script.';
            };
            document.head.appendChild(script);
        },

        mount() {
            if (!window.ArchitectCardProvider || typeof window.ArchitectCardProvider.mount !== 'function') {
                this.status = 'Loaded provider script does not implement window.ArchitectCardProvider.mount().';

                return;
            }

            window.ArchitectCardProvider.mount(this.$refs.mount, { publishableKey }, (token) => {
                this.$wire.set(wireField, token);
            });
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
    Alpine.data('architectPasswordStrength', architectPasswordStrength);
    Alpine.data('architectRanking', architectOrderableList);
    Alpine.data('architectSortableList', architectOrderableList);
    Alpine.data('architectCheckboxTree', architectCheckboxTree);
    Alpine.data('architectKeyboardShortcutRecorder', architectKeyboardShortcutRecorder);
    Alpine.data('architectDialKnob', architectDialKnob);
    Alpine.data('architectMaskedInput', architectMaskedInput);
    Alpine.data('architectCardInput', architectCardInput);
}
