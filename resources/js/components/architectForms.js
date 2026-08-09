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
import { CronExpressionParser } from 'cron-parser';

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

/**
 * Backs FormulaExpressionEditorField — bespoke input + field-reference
 * chip insertion + live highlighting of recognized field names
 * (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 4). Deliberately no
 * third-party expression-editor library per the dependency policy
 * table: evaluation is a host-app concern, this field only builds the
 * raw expression string.
 *
 * @param {Object}          options
 * @param {string}          options.wireField
 * @param {Array<string>}   options.availableFields
 */
function architectFormulaExpressionEditor({ wireField, availableFields = [] }) {
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

        insertField(name) {
            const input = this.$refs.input;
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            input.value = input.value.slice(0, start) + name + input.value.slice(end);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            this.onInput(input.value);
        },

        renderPreview(value) {
            const escaped = value.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[c]);

            if (availableFields.length === 0) {
                this.$refs.preview.innerHTML = escaped;

                return;
            }

            const sorted = [...availableFields].sort((a, b) => b.length - a.length);
            const pattern = new RegExp(`\\b(${sorted.map((f) => f.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})\\b`, 'g');
            this.$refs.preview.innerHTML = escaped.replace(pattern, (match) => `<span class="arch-formula-expression-editor__token">${match}</span>`);
        },
    };
}

/**
 * Backs MathEquationEditorField — lightweight LaTeX snippet palette +
 * raw-string editing (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 4).
 * Deliberately vanilla, no MathQuill: the real npm `mathquill` package
 * hard-depends on jQuery 1.x, which conflicts with this codebase's
 * lightweight/no-legacy-dependency policy. The field's value is just
 * the resulting LaTeX string (visual rendering, if desired, is a
 * host-app concern via KaTeX/MathJax).
 *
 * @param {Object} options
 * @param {string} options.wireField
 */
function architectMathEquationEditor({ wireField }) {
    return {
        value: '',

        init() {
            this.value = this.$wire.get(wireField) ?? '';
            this.$refs.editor.value = this.value;
        },

        onInput(value) {
            this.value = value;
            this.$wire.set(wireField, value);
        },

        insert(snippet, cursorOffset = 0) {
            const input = this.$refs.editor;
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            input.value = input.value.slice(0, start) + snippet + input.value.slice(end);
            const caret = start + snippet.length + cursorOffset;
            input.focus();
            input.setSelectionRange(caret, caret);
            this.onInput(input.value);
        },
    };
}

/**
 * Backs QueryBuilderField — recursive nested AND/OR condition-group
 * builder (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 4). Value shape:
 * {operator: 'and'|'or', conditions: Array<Condition|Group>}. Bespoke
 * imperative DOM (no library): the field never evaluates the tree
 * itself (host-app concern), so pulling in an evaluation library such
 * as json-logic-js would be dead weight.
 *
 * @param {Object}        options
 * @param {string}        options.wireField
 * @param {Array<string>} options.availableFields
 */
function architectQueryBuilder({ wireField, availableFields = [] }) {
    const emptyGroup = () => ({ operator: 'and', conditions: [] });
    const emptyCondition = () => ({ field: availableFields[0] ?? '', operator: '=', value: '' });

    return {
        root: emptyGroup(),

        init() {
            this.root = this.$wire.get(wireField) ?? emptyGroup();
            this.render();
        },

        commit() {
            this.$wire.set(wireField, this.root);
            this.render();
        },

        render() {
            const container = this.$refs.groups;
            container.innerHTML = '';
            container.appendChild(this.buildGroup(this.root, []));
        },

        groupAt(path) {
            return path.reduce((group, index) => group.conditions[index], this.root);
        },

        buildGroup(group, path) {
            const wrapper = document.createElement('div');
            wrapper.className = 'arch-query-builder__group';

            const toolbar = document.createElement('div');
            toolbar.className = 'arch-query-builder__group-toolbar';

            const operatorSelect = document.createElement('select');
            operatorSelect.className = 'arch-select';
            ['and', 'or'].forEach((op) => {
                const option = document.createElement('option');
                option.value = op;
                option.textContent = op.toUpperCase();
                option.selected = group.operator === op;
                operatorSelect.appendChild(option);
            });
            operatorSelect.addEventListener('change', () => {
                group.operator = operatorSelect.value;
                this.commit();
            });
            toolbar.appendChild(operatorSelect);

            const addConditionBtn = document.createElement('button');
            addConditionBtn.type = 'button';
            addConditionBtn.className = 'arch-button';
            addConditionBtn.dataset.variant = 'outline';
            addConditionBtn.dataset.size = 'sm';
            addConditionBtn.textContent = 'Add condition';
            addConditionBtn.addEventListener('click', () => {
                group.conditions.push(emptyCondition());
                this.commit();
            });
            toolbar.appendChild(addConditionBtn);

            const addGroupBtn = document.createElement('button');
            addGroupBtn.type = 'button';
            addGroupBtn.className = 'arch-button';
            addGroupBtn.dataset.variant = 'outline';
            addGroupBtn.dataset.size = 'sm';
            addGroupBtn.textContent = 'Add group';
            addGroupBtn.addEventListener('click', () => {
                group.conditions.push(emptyGroup());
                this.commit();
            });
            toolbar.appendChild(addGroupBtn);

            wrapper.appendChild(toolbar);

            const rows = document.createElement('div');
            rows.className = 'arch-query-builder__rows';
            group.conditions.forEach((entry, index) => {
                const row = 'conditions' in entry
                    ? this.buildGroup(entry, [...path, index])
                    : this.buildCondition(entry, group, index);
                rows.appendChild(row);
            });
            wrapper.appendChild(rows);

            return wrapper;
        },

        buildCondition(condition, parentGroup, index) {
            const row = document.createElement('div');
            row.className = 'arch-query-builder__condition';

            const fieldSelect = document.createElement('select');
            fieldSelect.className = 'arch-select';
            availableFields.forEach((f) => {
                const option = document.createElement('option');
                option.value = f;
                option.textContent = f;
                option.selected = condition.field === f;
                fieldSelect.appendChild(option);
            });
            fieldSelect.addEventListener('change', () => {
                condition.field = fieldSelect.value;
                this.commit();
            });
            row.appendChild(fieldSelect);

            const operatorSelect = document.createElement('select');
            operatorSelect.className = 'arch-select';
            ['=', '!=', '>', '>=', '<', '<=', 'contains'].forEach((op) => {
                const option = document.createElement('option');
                option.value = op;
                option.textContent = op;
                option.selected = condition.operator === op;
                operatorSelect.appendChild(option);
            });
            operatorSelect.addEventListener('change', () => {
                condition.operator = operatorSelect.value;
                this.commit();
            });
            row.appendChild(operatorSelect);

            const valueInput = document.createElement('input');
            valueInput.type = 'text';
            valueInput.className = 'arch-input';
            valueInput.value = condition.value ?? '';
            valueInput.addEventListener('input', () => {
                condition.value = valueInput.value;
                this.commit();
            });
            row.appendChild(valueInput);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'arch-repeater__remove';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', () => {
                parentGroup.conditions.splice(index, 1);
                this.commit();
            });
            row.appendChild(removeBtn);

            return row;
        },
    };
}

/**
 * Backs RulesWorkflowBuilderField — ordered node list + from/to edge
 * list (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 4). Deliberately a
 * structured list builder rather than a drag-positioned canvas: the
 * field's own value shape has no (x, y) coordinates (unlike
 * NodeGraphEditorField), so a full 2D canvas would be surface without
 * substance. No json-logic-js: the field only builds the node/edge
 * data, it never evaluates it (host-app concern).
 *
 * @param {Object}        options
 * @param {string}        options.wireField
 * @param {Array<string>} options.availableNodeTypes
 */
function architectRulesWorkflowBuilder({ wireField, availableNodeTypes = [] }) {
    let nodeSeq = 0;

    return {
        nodes: [],
        edges: [],

        init() {
            const initial = this.$wire.get(wireField) ?? { nodes: [], edges: [] };
            this.nodes = initial.nodes ?? [];
            this.edges = initial.edges ?? [];
            nodeSeq = this.nodes.length;
            this.render();
        },

        commit() {
            this.$wire.set(wireField, { nodes: this.nodes, edges: this.edges });
            this.render();
        },

        addNode() {
            this.nodes.push({ id: `node-${nodeSeq++}`, type: availableNodeTypes[0] ?? '', config: {} });
            this.commit();
        },

        addEdge() {
            if (this.nodes.length < 2) return;
            this.edges.push({ from: this.nodes[0].id, to: this.nodes[1].id });
            this.commit();
        },

        render() {
            this.renderNodes();
            this.renderEdges();
        },

        renderNodes() {
            const container = this.$refs.nodes;
            container.innerHTML = '';
            this.nodes.forEach((node, index) => {
                const row = document.createElement('div');
                row.className = 'arch-rules-workflow-builder__node';

                const idLabel = document.createElement('span');
                idLabel.className = 'arch-badge';
                idLabel.dataset.color = 'primary';
                idLabel.dataset.variant = 'soft';
                idLabel.textContent = node.id;
                row.appendChild(idLabel);

                const typeSelect = document.createElement('select');
                typeSelect.className = 'arch-select';
                availableNodeTypes.forEach((type) => {
                    const option = document.createElement('option');
                    option.value = type;
                    option.textContent = type;
                    option.selected = node.type === type;
                    typeSelect.appendChild(option);
                });
                typeSelect.addEventListener('change', () => {
                    node.type = typeSelect.value;
                    this.commit();
                });
                row.appendChild(typeSelect);

                const configInput = document.createElement('input');
                configInput.type = 'text';
                configInput.className = 'arch-input arch-input--code';
                configInput.value = JSON.stringify(node.config ?? {});
                configInput.addEventListener('change', () => {
                    try {
                        node.config = JSON.parse(configInput.value || '{}');
                        this.commit();
                    } catch {
                        configInput.value = JSON.stringify(node.config ?? {});
                    }
                });
                row.appendChild(configInput);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'arch-repeater__remove';
                removeBtn.textContent = 'Remove';
                removeBtn.addEventListener('click', () => {
                    this.nodes.splice(index, 1);
                    this.edges = this.edges.filter((e) => e.from !== node.id && e.to !== node.id);
                    this.commit();
                });
                row.appendChild(removeBtn);

                container.appendChild(row);
            });
        },

        renderEdges() {
            const container = this.$refs.edges;
            container.innerHTML = '';
            this.edges.forEach((edge, index) => {
                const row = document.createElement('div');
                row.className = 'arch-rules-workflow-builder__edge';

                const fromSelect = this.nodeSelect(edge.from, (value) => { edge.from = value; this.commit(); });
                const toSelect = this.nodeSelect(edge.to, (value) => { edge.to = value; this.commit(); });
                row.appendChild(fromSelect);

                const arrow = document.createElement('span');
                arrow.textContent = '→';
                row.appendChild(arrow);
                row.appendChild(toSelect);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'arch-repeater__remove';
                removeBtn.textContent = 'Remove';
                removeBtn.addEventListener('click', () => {
                    this.edges.splice(index, 1);
                    this.commit();
                });
                row.appendChild(removeBtn);

                container.appendChild(row);
            });
        },

        nodeSelect(selectedId, onChange) {
            const select = document.createElement('select');
            select.className = 'arch-select';
            this.nodes.forEach((node) => {
                const option = document.createElement('option');
                option.value = node.id;
                option.textContent = node.id;
                option.selected = node.id === selectedId;
                select.appendChild(option);
            });
            select.addEventListener('change', () => onChange(select.value));

            return select;
        },
    };
}

/**
 * Backs SchemaDrivenObjectEditorField — renders one input per JSON
 * Schema property, recursing into nested `type: object` properties
 * (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 4). Bespoke, no
 * JSON-schema-form library per the dependency policy table.
 *
 * @param {Object} options
 * @param {string} options.wireField
 * @param {Object} options.schema  A JSON Schema object.
 */
function architectSchemaDrivenObjectEditor({ wireField, schema = {} }) {
    return {
        data: {},

        init() {
            this.data = this.$wire.get(wireField) ?? {};
            this.render();
        },

        commit() {
            this.$wire.set(wireField, this.data);
        },

        render() {
            const container = this.$refs.form;
            container.innerHTML = '';
            container.appendChild(this.buildObject(schema, this.data));
        },

        buildObject(objectSchema, target) {
            const wrapper = document.createElement('div');
            wrapper.className = 'arch-schema-driven-object-editor__object';
            const properties = objectSchema.properties ?? {};

            Object.entries(properties).forEach(([key, propSchema]) => {
                const field = document.createElement('label');
                field.className = 'arch-schema-driven-object-editor__field';

                const label = document.createElement('span');
                label.className = 'arch-field__label';
                label.textContent = propSchema.title ?? key;
                field.appendChild(label);

                field.appendChild(this.buildControl(propSchema, target, key));
                wrapper.appendChild(field);
            });

            return wrapper;
        },

        buildControl(propSchema, target, key) {
            if (propSchema.type === 'object') {
                target[key] = target[key] ?? {};

                return this.buildObject(propSchema, target[key]);
            }

            if (Array.isArray(propSchema.enum)) {
                const select = document.createElement('select');
                select.className = 'arch-select';
                propSchema.enum.forEach((value) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = value;
                    option.selected = target[key] === value;
                    select.appendChild(option);
                });
                select.addEventListener('change', () => {
                    target[key] = select.value;
                    this.commit();
                });

                return select;
            }

            if (propSchema.type === 'boolean') {
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = Boolean(target[key]);
                checkbox.addEventListener('change', () => {
                    target[key] = checkbox.checked;
                    this.commit();
                });

                return checkbox;
            }

            const input = document.createElement('input');
            input.type = propSchema.type === 'number' || propSchema.type === 'integer' ? 'number' : 'text';
            input.className = 'arch-input';
            input.value = target[key] ?? '';
            input.addEventListener('input', () => {
                target[key] = input.type === 'number' ? Number(input.value) : input.value;
                this.commit();
            });

            return input;
        },
    };
}

/**
 * Backs NodeGraphEditorField — draggable, positioned nodes connected by
 * SVG edges (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 4). Value
 * shape: {nodes: Array<{id, type, x, y}>, edges: Array<{from, to}>}.
 * Bespoke Pointer Events canvas, no graph-visualization library per the
 * dependency policy table.
 *
 * @param {Object}        options
 * @param {string}        options.wireField
 * @param {Array<string>} options.availableNodeTypes
 */
function architectNodeGraphEditor({ wireField, availableNodeTypes = [] }) {
    let nodeSeq = 0;

    return {
        nodes: [],
        edges: [],
        pendingConnection: null,

        init() {
            const initial = this.$wire.get(wireField) ?? { nodes: [], edges: [] };
            this.nodes = initial.nodes ?? [];
            this.edges = initial.edges ?? [];
            nodeSeq = this.nodes.length;
            this.render();
        },

        commit() {
            this.$wire.set(wireField, { nodes: this.nodes, edges: this.edges });
            this.renderEdges();
        },

        addNode(type) {
            this.nodes.push({ id: `node-${nodeSeq++}`, type, x: 20 + (this.nodes.length * 30) % 200, y: 20 + (this.nodes.length * 40) % 200 });
            this.commit();
            this.renderNodes();
        },

        beginConnection(nodeId) {
            this.pendingConnection = nodeId;
        },

        completeConnection(nodeId) {
            if (!this.pendingConnection || this.pendingConnection === nodeId) {
                this.pendingConnection = null;

                return;
            }
            this.edges.push({ from: this.pendingConnection, to: nodeId });
            this.pendingConnection = null;
            this.commit();
        },

        render() {
            this.renderNodes();
            this.renderEdges();
        },

        renderNodes() {
            const canvas = this.$refs.canvas;
            canvas.querySelectorAll('.arch-node-graph-editor__node').forEach((el) => el.remove());

            this.nodes.forEach((node) => {
                const el = document.createElement('div');
                el.className = 'arch-node-graph-editor__node';
                el.style.left = `${node.x}px`;
                el.style.top = `${node.y}px`;
                el.textContent = `${node.type} (${node.id})`;

                el.addEventListener('click', (event) => {
                    if (event.shiftKey) {
                        this.completeConnection(node.id);
                    } else {
                        this.beginConnection(node.id);
                    }
                });

                let dragging = false;
                el.addEventListener('pointerdown', (event) => {
                    dragging = true;
                    el.setPointerCapture(event.pointerId);
                });
                el.addEventListener('pointermove', (event) => {
                    if (!dragging) return;
                    const canvasRect = canvas.getBoundingClientRect();
                    node.x = Math.max(0, event.clientX - canvasRect.left - el.offsetWidth / 2);
                    node.y = Math.max(0, event.clientY - canvasRect.top - el.offsetHeight / 2);
                    el.style.left = `${node.x}px`;
                    el.style.top = `${node.y}px`;
                    this.renderEdges();
                });
                el.addEventListener('pointerup', () => {
                    if (!dragging) return;
                    dragging = false;
                    this.commit();
                });

                canvas.appendChild(el);
            });
        },

        renderEdges() {
            const svg = this.$refs.svg;
            svg.innerHTML = '';

            this.edges.forEach((edge) => {
                const from = this.nodes.find((n) => n.id === edge.from);
                const to = this.nodes.find((n) => n.id === edge.to);
                if (!from || !to) return;

                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', String(from.x + 40));
                line.setAttribute('y1', String(from.y + 16));
                line.setAttribute('x2', String(to.x + 40));
                line.setAttribute('y2', String(to.y + 16));
                line.setAttribute('class', 'arch-node-graph-editor__edge-line');
                svg.appendChild(line);
            });
        },
    };
}

/**
 * Backs DataMappingField — repeatable {source, destination, transform}
 * rows (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 4).
 *
 * @param {Object}        options
 * @param {string}        options.wireField
 * @param {Array<string>} options.sourceFields
 * @param {Array<string>} options.destinationFields
 */
function architectDataMapping({ wireField, sourceFields = [], destinationFields = [] }) {
    return {
        rows: [],

        init() {
            this.rows = this.$wire.get(wireField) ?? [];
            this.render();
        },

        commit() {
            this.$wire.set(wireField, this.rows);
            this.render();
        },

        addRow() {
            this.rows.push({ source: sourceFields[0] ?? '', destination: destinationFields[0] ?? '', transform: null });
            this.commit();
        },

        render() {
            const container = this.$refs.rows;
            container.innerHTML = '';

            this.rows.forEach((row, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'arch-data-mapping__row';

                const sourceSelect = this.optionSelect(sourceFields, row.source, (value) => { row.source = value; this.commit(); });
                wrapper.appendChild(sourceSelect);

                const arrow = document.createElement('span');
                arrow.textContent = '→';
                wrapper.appendChild(arrow);

                const destinationSelect = this.optionSelect(destinationFields, row.destination, (value) => { row.destination = value; this.commit(); });
                wrapper.appendChild(destinationSelect);

                const transformInput = document.createElement('input');
                transformInput.type = 'text';
                transformInput.className = 'arch-input';
                transformInput.placeholder = 'Transform (optional)';
                transformInput.value = row.transform ?? '';
                transformInput.addEventListener('input', () => {
                    row.transform = transformInput.value === '' ? null : transformInput.value;
                    this.commit();
                });
                wrapper.appendChild(transformInput);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'arch-repeater__remove';
                removeBtn.textContent = 'Remove';
                removeBtn.addEventListener('click', () => {
                    this.rows.splice(index, 1);
                    this.commit();
                });
                wrapper.appendChild(removeBtn);

                container.appendChild(wrapper);
            });
        },

        optionSelect(values, selected, onChange) {
            const select = document.createElement('select');
            select.className = 'arch-select';
            values.forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                option.selected = value === selected;
                select.appendChild(option);
            });
            select.addEventListener('change', () => onChange(select.value));

            return select;
        },
    };
}

/**
 * Backs CronScheduleBuilderField — friendly per-field schedule builder
 * that assembles a 5-field cron expression, validated (and previewed
 * via next-3-run-times) using cron-parser
 * (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1 Wave 4). The builder UI itself
 * stays bespoke per the dependency policy table; cron-parser handles
 * only validation/next-run computation, matching its real strength
 * (correctly handling step values, ranges, and DST).
 *
 * @param {Object} options
 * @param {string} options.wireField
 */
function architectCronScheduleBuilder({ wireField }) {
    const fieldDefs = [
        { key: 'minute', label: 'Minute' },
        { key: 'hour', label: 'Hour' },
        { key: 'dayOfMonth', label: 'Day of month' },
        { key: 'month', label: 'Month' },
        { key: 'dayOfWeek', label: 'Day of week' },
    ];

    return {
        parts: { minute: '*', hour: '*', dayOfMonth: '*', month: '*', dayOfWeek: '*' },

        init() {
            const existing = this.$wire.get(wireField) ?? '';
            const segments = existing.trim().split(/\s+/);
            if (segments.length === 5) {
                fieldDefs.forEach((def, index) => { this.parts[def.key] = segments[index]; });
            }
            this.render();
            this.commit();
        },

        expression() {
            return fieldDefs.map((def) => this.parts[def.key]).join(' ');
        },

        commit() {
            const expression = this.expression();
            this.$wire.set(wireField, expression);
            this.updatePreview(expression);
        },

        updatePreview(expression) {
            try {
                const interval = CronExpressionParser.parse(expression);
                const next = interval.take(3).map((date) => date.toString());
                this.$refs.preview.textContent = `Next: ${next.join(', ')}`;
            } catch (error) {
                this.$refs.preview.textContent = `Invalid schedule: ${error.message}`;
            }
        },

        render() {
            const container = this.$refs.builder;
            container.innerHTML = '';

            fieldDefs.forEach((def) => {
                const label = document.createElement('label');
                label.className = 'arch-cron-schedule-builder__field';

                const span = document.createElement('span');
                span.className = 'arch-field__label';
                span.textContent = def.label;
                label.appendChild(span);

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'arch-input arch-input--code';
                input.value = this.parts[def.key];
                input.addEventListener('input', () => {
                    this.parts[def.key] = input.value === '' ? '*' : input.value;
                    this.commit();
                });
                label.appendChild(input);

                container.appendChild(label);
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
    Alpine.data('architectFormulaExpressionEditor', architectFormulaExpressionEditor);
    Alpine.data('architectMathEquationEditor', architectMathEquationEditor);
    Alpine.data('architectQueryBuilder', architectQueryBuilder);
    Alpine.data('architectRulesWorkflowBuilder', architectRulesWorkflowBuilder);
    Alpine.data('architectSchemaDrivenObjectEditor', architectSchemaDrivenObjectEditor);
    Alpine.data('architectNodeGraphEditor', architectNodeGraphEditor);
    Alpine.data('architectDataMapping', architectDataMapping);
    Alpine.data('architectCronScheduleBuilder', architectCronScheduleBuilder);
}
