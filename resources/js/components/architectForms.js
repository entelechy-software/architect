/**
 * architectForms — Alpine.js components backing the interactive Forms
 * field views added in Phase 4 (resources/views/forms/fields/*.blade.php).
 *
 * Each export is registered as an Alpine.data() factory and instantiated
 * via x-data="architectXyz({...})" in the matching Blade view.
 */
import Sortable from 'sortablejs';
import Cropper from 'cropperjs';
import Tribute from 'tributejs';

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

/**
 * Backs KanbanBoardField — drag-between-columns board (ARCHITECT_IMPROVEMENT_PLAN.md
 * Phase 1 Wave 2). Uses SortableJS (per the Wave 2 dependency policy table)
 * since, unlike Wave 1's single-list Ranking/SortableList, this needs
 * cross-column dragging that native HTML5 drag-and-drop makes painful to
 * implement correctly (drop-target detection across sibling containers).
 *
 * @param {Object}               options
 * @param {string}               options.wireField
 * @param {Array<string>}        options.columns
 * @param {Object<string,string>} options.items  itemKey => card label.
 */
function architectKanbanBoard({ wireField, columns = [], items = {} }) {
    return {
        board: {},

        init() {
            const stored = this.$wire.get(wireField);
            this.board = stored && Object.keys(stored).length > 0
                ? stored
                : { [columns[0]]: Object.keys(items), ...Object.fromEntries(columns.slice(1).map((c) => [c, []])) };

            this.$el.querySelectorAll('[data-column-items]').forEach((container) => {
                const column = container.dataset.columnItems;
                (this.board[column] ?? []).forEach((itemKey) => container.appendChild(this.buildCard(itemKey)));

                Sortable.create(container, {
                    group: `architect-kanban-${wireField}`,
                    animation: 150,
                    onEnd: () => this.syncBoard(),
                });
            });
        },

        buildCard(itemKey) {
            const card = document.createElement('div');
            card.className = 'arch-kanban-board__card';
            card.dataset.item = itemKey;
            card.textContent = items[itemKey] ?? itemKey;

            return card;
        },

        syncBoard() {
            const board = {};
            this.$el.querySelectorAll('[data-column-items]').forEach((container) => {
                board[container.dataset.columnItems] = Array.from(container.children).map((el) => el.dataset.item);
            });
            this.board = board;
            this.$wire.set(wireField, board);
        },
    };
}

/**
 * Backs ImageCropperField — Cropper.js-powered crop/rotate/zoom step
 * before the file is handed to Livewire's native wire:model upload
 * (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 2). Never uploads the
 * original file: the cropped output replaces the input's FileList (via
 * DataTransfer) before Livewire's own change listener fires the upload.
 *
 * @param {Object}      options
 * @param {string}      options.wireField
 * @param {number|null} options.aspectRatio
 */
function architectImageCropper({ wireField, aspectRatio = null }) {
    return {
        cropper: null,
        cropping: false,

        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = () => {
                this.$refs.canvas.innerHTML = '';
                const img = document.createElement('img');
                img.src = reader.result;
                img.style.maxWidth = '100%';
                this.$refs.canvas.appendChild(img);

                this.cropper?.destroy();
                this.cropper = new Cropper(img, { aspectRatio: aspectRatio ?? NaN, viewMode: 1 });
                this.cropping = true;
            };
            reader.readAsDataURL(file);
        },

        applyCrop(inputEl) {
            if (!this.cropper) return;

            this.cropper.getCroppedCanvas().toBlob((blob) => {
                const croppedFile = new File([blob], 'cropped.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);
                inputEl.files = dataTransfer.files;
                inputEl.dispatchEvent(new Event('change', { bubbles: true }));

                this.cropper.destroy();
                this.cropper = null;
                this.cropping = false;
            }, 'image/jpeg', 0.92);
        },

        cancelCrop() {
            this.cropper?.destroy();
            this.cropper = null;
            this.cropping = false;
            this.$refs.canvas.innerHTML = '';
            this.$refs.input.value = '';
        },
    };
}

/**
 * Backs ImageComparisonSliderField — draggable divider between a before
 * and after image, using Pointer Events (no third-party lib — Wave 2's
 * dependency table flags this one as "likely no dependency needed").
 *
 * @param {Object}      options
 * @param {string}      options.wireField
 * @param {string|null} options.beforeImageUrl
 * @param {string|null} options.afterImageUrl
 */
function architectImageComparisonSlider({ wireField, beforeImageUrl = null, afterImageUrl = null }) {
    return {
        position: 50,

        init() {
            this.position = this.$wire.get(wireField) ?? 50;

            const container = this.$refs.slider;
            container.classList.add('arch-image-comparison-slider__container');

            const before = document.createElement('img');
            before.className = 'arch-image-comparison-slider__before';
            before.src = beforeImageUrl ?? '';

            const afterWrap = document.createElement('div');
            afterWrap.className = 'arch-image-comparison-slider__after-wrap';
            const after = document.createElement('img');
            after.className = 'arch-image-comparison-slider__after';
            after.src = afterImageUrl ?? '';
            afterWrap.appendChild(after);

            const handle = document.createElement('div');
            handle.className = 'arch-image-comparison-slider__handle';

            container.append(before, afterWrap, handle);
            this._afterWrap = afterWrap;
            this._handle = handle;
            this.updatePosition(this.position);

            let dragging = false;
            const move = (clientX) => {
                const rect = container.getBoundingClientRect();
                const pct = Math.min(100, Math.max(0, ((clientX - rect.left) / rect.width) * 100));
                this.updatePosition(pct);
                this.$wire.set(wireField, pct);
            };

            handle.addEventListener('pointerdown', (e) => {
                dragging = true;
                handle.setPointerCapture(e.pointerId);
            });
            handle.addEventListener('pointermove', (e) => { if (dragging) move(e.clientX); });
            handle.addEventListener('pointerup', () => { dragging = false; });
        },

        updatePosition(pct) {
            this.position = pct;
            this._afterWrap.style.width = `${pct}%`;
            this._handle.style.left = `${pct}%`;
        },
    };
}

/**
 * Backs GradientEditorField — multi-stop CSS linear-gradient editor
 * (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 2, "extends existing
 * ColorPicker patterns" — plain <input type="color"> per stop, no
 * third-party lib).
 *
 * @param {Object} options
 * @param {string} options.wireField
 */
function architectGradientEditor({ wireField }) {
    return {
        angle: 90,
        stops: [],

        init() {
            const stored = this.$wire.get(wireField);
            this.angle = stored?.angle ?? 90;
            this.stops = stored?.stops?.length ? stored.stops : [
                { color: '#3b82f6', position: 0 },
                { color: '#8b5cf6', position: 100 },
            ];
            this.$refs.angle.value = this.angle;
            this.render();
        },

        onAngleInput(value) {
            this.angle = Number(value);
            this.sync();
        },

        addStop() {
            this.stops.push({ color: '#ffffff', position: 50 });
            this.sync();
        },

        removeStop(index) {
            this.stops.splice(index, 1);
            this.sync();
        },

        updateStop(index, key, value) {
            this.stops[index][key] = key === 'position' ? Number(value) : value;
            this.sync();
        },

        sync() {
            this.$wire.set(wireField, { angle: this.angle, stops: this.stops });
            this.render();
        },

        gradientCss() {
            const stops = [...this.stops]
                .sort((a, b) => a.position - b.position)
                .map((s) => `${s.color} ${s.position}%`)
                .join(', ');

            return `linear-gradient(${this.angle}deg, ${stops})`;
        },

        render() {
            this.$refs.preview.style.background = this.gradientCss();

            const container = this.$refs.stops;
            container.innerHTML = '';
            this.stops.forEach((stop, index) => {
                const row = document.createElement('div');
                row.className = 'arch-gradient-editor__stop';

                const color = document.createElement('input');
                color.type = 'color';
                color.value = stop.color;
                color.addEventListener('input', (e) => this.updateStop(index, 'color', e.target.value));

                const position = document.createElement('input');
                position.type = 'range';
                position.min = 0;
                position.max = 100;
                position.value = stop.position;
                position.addEventListener('input', (e) => this.updateStop(index, 'position', e.target.value));

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'arch-btn-close';
                remove.textContent = '×';
                remove.addEventListener('click', () => this.removeStop(index));

                row.append(color, position, remove);
                container.appendChild(row);
            });
        },
    };
}

/**
 * Backs EntityPickerField — richer templated search results (avatar,
 * subtitle) than the plain-label LookupField/architectCombobox
 * (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 2). Expects searchUrl's
 * JSON response as `[{id, label, subtitle?, avatar?}, ...]`.
 *
 * @param {Object}  options
 * @param {string}  options.wireField
 * @param {string|null} options.searchUrl
 * @param {boolean} options.multiple
 */
function architectEntityPicker({ wireField, searchUrl = null, multiple = false }) {
    return {
        results: [],
        selected: multiple ? [] : null,
        _debounce: null,

        init() {
            this.selected = this.$wire.get(wireField) ?? (multiple ? [] : null);
            this.$refs.search.addEventListener('input', (e) => {
                clearTimeout(this._debounce);
                this._debounce = setTimeout(() => this.search(e.target.value), 250);
            });
        },

        async search(term) {
            if (!searchUrl) return;
            try {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(term)}`);
                const data = await response.json();
                this.results = Array.isArray(data) ? data : (data.results ?? []);
            } catch {
                this.results = [];
            }
            this.render();
        },

        select(result) {
            if (multiple) {
                if (!this.selected.includes(result.id)) this.selected.push(result.id);
            } else {
                this.selected = result.id;
            }
            this.$wire.set(wireField, this.selected);
            this.render();
        },

        render() {
            const container = this.$refs.results;
            container.innerHTML = '';
            this.results.forEach((result) => {
                const card = document.createElement('div');
                card.className = 'arch-entity-picker__card';
                card.dataset.selected = String(multiple ? this.selected.includes(result.id) : this.selected === result.id);

                if (result.avatar) {
                    const avatar = document.createElement('img');
                    avatar.className = 'arch-entity-picker__avatar';
                    avatar.src = result.avatar;
                    card.appendChild(avatar);
                }

                const text = document.createElement('div');
                const label = document.createElement('div');
                label.className = 'arch-entity-picker__label';
                label.textContent = result.label;
                text.appendChild(label);

                if (result.subtitle) {
                    const subtitle = document.createElement('div');
                    subtitle.className = 'arch-entity-picker__subtitle';
                    subtitle.textContent = result.subtitle;
                    text.appendChild(subtitle);
                }

                card.appendChild(text);
                card.addEventListener('click', () => this.select(result));
                container.appendChild(card);
            });
        },
    };
}

/**
 * Backs RelationshipPickerField — links this record to another
 * record/event/entity of a chosen type (ARCHITECT_IMPROVEMENT_PLAN.md
 * Phase 1 Wave 2). searchUrl receives `?type=...&q=...`.
 *
 * @param {Object}        options
 * @param {string}        options.wireField
 * @param {Array<string>} options.allowedTypes
 * @param {string|null}   options.searchUrl
 */
function architectRelationshipPicker({ wireField, allowedTypes = [], searchUrl = null }) {
    return {
        results: [],
        _debounce: null,

        init() {
            const stored = this.$wire.get(wireField);
            if (stored?.type) this.$refs.type.value = stored.type;
        },

        onTypeChanged() {
            this.search(this.$refs.search.value);
        },

        onSearchInput(value) {
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => this.search(value), 250);
        },

        async search(term) {
            if (!searchUrl) return;
            const type = this.$refs.type.value;
            try {
                const response = await fetch(`${searchUrl}?type=${encodeURIComponent(type)}&q=${encodeURIComponent(term)}`);
                const data = await response.json();
                this.results = Array.isArray(data) ? data : (data.results ?? []);
            } catch {
                this.results = [];
            }
            this.render();
        },

        select(result) {
            const value = { type: this.$refs.type.value, id: result.id };
            this.$wire.set(wireField, value);
        },

        render() {
            const container = this.$refs.results;
            container.innerHTML = '';
            this.results.forEach((result) => {
                const item = document.createElement('div');
                item.className = 'arch-relationship-picker__result';
                item.textContent = result.label ?? String(result.id);
                item.addEventListener('click', () => this.select(result));
                container.appendChild(item);
            });
        },
    };
}

/**
 * Backs TreeSelectField — a dropdown-free hierarchical single-select tree
 * (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 2). Branch nodes toggle
 * expand/collapse; leaves (and branches too, when selectableBranches is
 * true) are selectable.
 *
 * @param {Object}  options
 * @param {string}  options.wireField
 * @param {Array}   options.tree
 * @param {boolean} options.selectableBranches
 */
function architectTreeSelect({ wireField, tree = [], selectableBranches = false }) {
    return {
        selected: null,
        expanded: [],

        init() {
            this.selected = this.$wire.get(wireField) ?? null;
            this.render();
        },

        select(node) {
            this.selected = node.key;
            this.$wire.set(wireField, node.key);
            this.render();
        },

        toggleExpanded(key) {
            this.expanded = this.expanded.includes(key)
                ? this.expanded.filter((k) => k !== key)
                : [...this.expanded, key];
            this.render();
        },

        render() {
            const container = this.$refs.tree;
            container.innerHTML = '';
            tree.forEach((node) => container.appendChild(this.buildNode(node)));
        },

        buildNode(node) {
            const wrapper = document.createElement('div');
            wrapper.className = 'arch-tree-select__node';

            const row = document.createElement('div');
            row.className = 'arch-tree-select__row';
            row.dataset.selected = String(this.selected === node.key);

            const hasChildren = (node.children ?? []).length > 0;
            if (hasChildren) {
                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'arch-tree-select__toggle';
                toggle.textContent = this.expanded.includes(node.key) ? '▾' : '▸';
                toggle.addEventListener('click', () => this.toggleExpanded(node.key));
                row.appendChild(toggle);
            }

            const label = document.createElement('span');
            label.className = 'arch-tree-select__label';
            label.textContent = node.label;
            if (!hasChildren || selectableBranches) {
                label.addEventListener('click', () => this.select(node));
            }
            row.appendChild(label);
            wrapper.appendChild(row);

            if (hasChildren && this.expanded.includes(node.key)) {
                const children = document.createElement('div');
                children.className = 'arch-tree-select__children';
                node.children.forEach((child) => children.appendChild(this.buildNode(child)));
                wrapper.appendChild(children);
            }

            return wrapper;
        },
    };
}

/**
 * Backs DualListboxField — click-to-highlight-then-transfer between two
 * panes (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 2). Deliberately
 * click+button rather than drag-and-drop: simpler, more accessible, and
 * just as real.
 *
 * @param {Object}                options
 * @param {string}                options.wireField
 * @param {Object<string,string>} options.options  key => label pairs.
 */
function architectDualListbox({ wireField, options = {} }) {
    return {
        selectedKeys: [],
        highlighted: [],

        init() {
            this.selectedKeys = this.$wire.get(wireField) ?? [];
            this.render();
        },

        toggleHighlight(key) {
            this.highlighted = this.highlighted.includes(key)
                ? this.highlighted.filter((k) => k !== key)
                : [...this.highlighted, key];
            this.render();
        },

        moveToSelected() {
            this.highlighted.forEach((key) => {
                if (!this.selectedKeys.includes(key)) this.selectedKeys.push(key);
            });
            this.highlighted = [];
            this.sync();
        },

        moveToAvailable() {
            this.selectedKeys = this.selectedKeys.filter((key) => !this.highlighted.includes(key));
            this.highlighted = [];
            this.sync();
        },

        sync() {
            this.$wire.set(wireField, this.selectedKeys);
            this.render();
        },

        buildPane(keys) {
            const pane = document.createElement('div');
            pane.className = 'arch-dual-listbox__list';
            keys.forEach((key) => {
                const item = document.createElement('div');
                item.className = 'arch-dual-listbox__item';
                item.dataset.highlighted = String(this.highlighted.includes(key));
                item.textContent = options[key] ?? key;
                item.addEventListener('click', () => this.toggleHighlight(key));
                pane.appendChild(item);
            });

            return pane;
        },

        render() {
            const availableKeys = Object.keys(options).filter((key) => !this.selectedKeys.includes(key));

            this.$refs.available.innerHTML = '';
            this.$refs.available.appendChild(this.buildPane(availableKeys));

            this.$refs.selected.innerHTML = '';
            this.$refs.selected.appendChild(this.buildPane(this.selectedKeys));
        },
    };
}

/**
 * Backs TemplateEditorField — `{{ variable }}` placeholder editor with a
 * click-to-insert variable list and a structural preview that highlights
 * recognized vs. unrecognized tokens (ARCHITECT_IMPROVEMENT_PLAN.md Phase
 * 1 Wave 2). Never renders against real data — that's a host-app concern
 * per the field's own docblock — this only validates token names.
 *
 * @param {Object}        options
 * @param {string}        options.wireField
 * @param {Array<string>} options.availableVariables
 */
function architectTemplateEditor({ wireField, availableVariables = [] }) {
    return {
        init() {
            const value = this.$wire.get(wireField) ?? '';
            this.$refs.input.value = value;
            this.renderPreview(value);
        },

        onInput(value) {
            this.$wire.set(wireField, value);
            this.renderPreview(value);
        },

        insertVariable(name) {
            const input = this.$refs.input;
            const token = `{{ ${name} }}`;
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            input.value = input.value.slice(0, start) + token + input.value.slice(end);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            this.onInput(input.value);
        },

        renderPreview(value) {
            const escaped = value.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[c]);
            const highlighted = escaped.replace(/\{\{\s*([\w.]+)\s*\}\}/g, (match, name) => {
                const known = availableVariables.includes(name);
                const cls = known ? 'arch-template-editor__token--known' : 'arch-template-editor__token--unknown';

                return `<span class="${cls}">${match}</span>`;
            });
            this.$refs.preview.innerHTML = highlighted || `<em>${'Preview'}</em>`;
        },
    };
}

/**
 * Backs MentionEditorField — @mention autocomplete via Tribute.js (per
 * the Wave 2 dependency policy table: headless-style, you render/style
 * the suggestion menu) over a contenteditable div
 * (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 2). Expects
 * mentionableUrl's JSON response as `[{key, value}, ...]` (Tribute's
 * native shape).
 *
 * @param {Object}      options
 * @param {string}      options.wireField
 * @param {string|null} options.mentionableUrl
 */
function architectMentionEditor({ wireField, mentionableUrl = null }) {
    return {
        init() {
            const editor = this.$refs.editor;
            editor.innerHTML = this.$wire.get(wireField) ?? '';

            const tribute = new Tribute({
                values: (text, cb) => {
                    if (!mentionableUrl) {
                        cb([]);

                        return;
                    }

                    fetch(`${mentionableUrl}?q=${encodeURIComponent(text)}`)
                        .then((r) => r.json())
                        .then((data) => cb(Array.isArray(data) ? data : (data.results ?? [])))
                        .catch(() => cb([]));
                },
            });
            tribute.attach(editor);

            editor.addEventListener('input', () => this.$wire.set(wireField, editor.innerHTML));
            editor.addEventListener('tribute-replaced', () => this.$wire.set(wireField, editor.innerHTML));
        },
    };
}

/**
 * Enhances RegexBuilderTesterField (already Maturity::Stable — its plain
 * wire:model pattern/flags/sample inputs are functional — but its own
 * docblock promises "live match highlighting and captured groups" that
 * didn't exist yet) with a real live-testing panel. No third-party lib:
 * regex evaluation is a native JS engine capability.
 *
 * @param {Object} options
 * @param {string} options.wireField
 * @param {string} options.sampleText
 */
function architectRegexTester({ wireField, sampleText = '' }) {
    return {
        pattern: '',
        flags: '',
        groups: [],

        init() {
            const stored = this.$wire.get(wireField) ?? {};
            this.pattern = stored.pattern ?? '';
            this.flags = stored.flags ?? '';
            this.evaluate();
        },

        onPatternInput(value) {
            this.pattern = value;
            this.$wire.set(`${wireField}.pattern`, value);
            this.evaluate();
        },

        onFlagsInput(value) {
            this.flags = value;
            this.$wire.set(`${wireField}.flags`, value);
            this.evaluate();
        },

        evaluate() {
            this.groups = [];
            const escaped = sampleText.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[c]);

            if (!this.pattern) {
                this.$refs.highlighted.innerHTML = escaped;

                return;
            }

            try {
                const flags = this.flags.includes('g') ? this.flags : `${this.flags}g`;
                const re = new RegExp(this.pattern, flags);
                let lastIndex = 0;
                let html = '';
                let match;

                while ((match = re.exec(sampleText)) !== null) {
                    html += escaped.slice(lastIndex, match.index);
                    html += `<mark>${escaped.slice(match.index, match.index + match[0].length)}</mark>`;
                    lastIndex = match.index + match[0].length;
                    this.groups.push(match.slice(1));
                    if (match[0] === '') re.lastIndex++;
                }
                html += escaped.slice(lastIndex);

                this.$refs.highlighted.innerHTML = html;
            } catch {
                this.$refs.highlighted.innerHTML = escaped;
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
    Alpine.data('architectPasswordStrength', architectPasswordStrength);
    Alpine.data('architectRanking', architectOrderableList);
    Alpine.data('architectSortableList', architectOrderableList);
    Alpine.data('architectCheckboxTree', architectCheckboxTree);
    Alpine.data('architectKeyboardShortcutRecorder', architectKeyboardShortcutRecorder);
    Alpine.data('architectDialKnob', architectDialKnob);
    Alpine.data('architectMaskedInput', architectMaskedInput);
    Alpine.data('architectCardInput', architectCardInput);
    Alpine.data('architectKanbanBoard', architectKanbanBoard);
    Alpine.data('architectImageCropper', architectImageCropper);
    Alpine.data('architectImageComparisonSlider', architectImageComparisonSlider);
    Alpine.data('architectGradientEditor', architectGradientEditor);
    Alpine.data('architectEntityPicker', architectEntityPicker);
    Alpine.data('architectRelationshipPicker', architectRelationshipPicker);
    Alpine.data('architectTreeSelect', architectTreeSelect);
    Alpine.data('architectDualListbox', architectDualListbox);
    Alpine.data('architectTemplateEditor', architectTemplateEditor);
    Alpine.data('architectMentionEditor', architectMentionEditor);
    Alpine.data('architectRegexTester', architectRegexTester);
}
