{{--
    ModuleTable form panel — Tabler 1.0.0-beta17.

        Three render modes, set by $mode (driven by definition->formMode or
        explicit mount() param):
            - 'slide-over' (default): Architect slide-over panel.
            - 'modal':  Architect centered modal panel.
    - 'wizard': Full-card form rendered as the route's main content.

    Panel states ($panelState):
      - 'idle':   Panel is closed; no content rendered in body.
      - 'create': Create form (field loop + Save/Cancel footer).
      - 'edit':   Edit form (field loop + Save changes/Cancel footer).
      - 'view':   Read-only label/value list from Model::viewAll().
      - 'custom': Arbitrary Blade partial injected by a panelView() RowAction.

    Event contract (Livewire events this panel listens for):
      architect:open-create   { definitionClass }
      architect:open-edit     { definitionClass, id }
      architect:open-view     { definitionClass, id }
      architect:open-custom   { definitionClass, title, blade, data }
      architect:close-panel   (no payload)

    Custom Blade partials can close the panel by dispatching:
      $dispatch('architect:close-panel')
    And trigger a table refresh with:
      $dispatch('architect:refresh')
--}}
<div data-loading="{{ $isLoading ? 'true' : 'false' }}">
@if ($mode === 'wizard')
    {{-- ── Wizard mode: full-card form ──────────────────────────────── --}}
    <div class="container-xl py-4">
        <div class="arch-card">
            <form wire:submit.prevent="submit">
                <div class="arch-card-header">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-0">{{ $panelTitle }}</h2>
                </div>

                <div class="arch-card-body">
                    @if ($errorMessage)
                        <div class="arch-alert arch-alert-danger"
                            role="alert"
                            @if (config('architect.animations', true) && $definition->animateErrors)
                            x-data
                            x-init="$watch('$wire.errorMessage', v => {
                                if (!v) return;
                                $el.classList.remove('arch-shake');
                                void $el.offsetWidth;
                                $el.classList.add('arch-shake');
                            })"
                            @endif
                        >
                            {{ $errorMessage }}
                        </div>
                    @endif

                    @foreach ($columns as $column)
                        @php
                            $editKey = $column->getEditKey();
                            $value = $form[$editKey] ?? null;
                            $type = $column->getType() ?? 'text';
                            $hasError = $errors->has('form.'.$editKey);
                            $_vw = \Entelechy\Architect\Table\VisibleWhenAlpineCompiler::compile($column);
                            $canEdit = $this->canEditColumn($column, $isCreate);
                        @endphp
                        <div class="mb-3" wire:key="column-{{ $column->key() }}"
                            @if ($_vw) x-show="{{ $_vw }}" style="display:none" @endif>
                            @include('architect::table.partials.form-input', ['column' => $column, 'editKey' => $editKey, 'value' => $value, 'type' => $type, 'hasError' => $hasError, 'canEdit' => $canEdit])
                        </div>
                    @endforeach
                </div>

                <div class="arch-card-footer flex justify-end gap-2">
                    <button
                        type="button"
                        class="arch-btn arch-btn-link link-secondary"
                        wire:click="close"
                    >
                        Cancel
                    </button>
                    <button type="submit" class="arch-btn arch-btn-primary">
                        <i class="fas fa-save ml-1"></i>
                        {{ $isCreate ? 'Create' : 'Save changes' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@elseif ($mode === 'modal')
    {{-- ── Modal mode: Architect overlay panel ────────────────────────────── --}}
    <div
        x-data="{ open: @entangle('open').live }"
        x-effect="
            if (open) {
                document.body.classList.add('modal-open');
            } else {
                document.body.classList.remove('modal-open');
            }
        "
    >
        {{-- Backdrop --}}
        <div
            class="arch-panel-backdrop"
            x-show="open"
            x-transition.opacity
            style="display:none"
        ></div>

        {{-- Modal --}}
        <div
            class="arch-panel arch-panel--modal"
            x-show="open"
            x-transition.opacity
            style="display:none"
            aria-hidden="false"
        >
            <div
                class="arch-panel__window arch-panel__window--modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="module-table-panel-title"
                x-transition:enter="transition arch-ease-bounce duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                    @if ($panelState === 'create' || $panelState === 'edit')
                    <form wire:submit.prevent="submit">
                    @endif

                        <div class="arch-panel__header">
                            <div class="arch-panel__header-copy">
                                <h2 class="arch-panel__title" id="module-table-panel-title">{{ $panelTitle }}</h2>
                            </div>
                            <button type="button" class="arch-panel__close" aria-label="Close" wire:click="close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="arch-panel__body">
                            @if ($errorMessage && ($panelState === 'create' || $panelState === 'edit'))
                                <div class="arch-alert arch-alert-danger"
                                    role="alert"
                                    @if (config('architect.animations', true) && $definition->animateErrors)
                                    x-data
                                    x-init="$watch('$wire.errorMessage', v => {
                                        if (!v) return;
                                        $el.classList.remove('arch-shake');
                                        void $el.offsetWidth;
                                        $el.classList.add('arch-shake');
                                    })"
                                    @endif
                                >{{ $errorMessage }}</div>
                            @endif

                            @if ($panelState === 'create' || $panelState === 'edit')
                                @foreach ($columns as $column)
                                    @php
                                        $editKey = $column->getEditKey();
                                        $value = $form[$editKey] ?? null;
                                        $type = $column->getType() ?? 'text';
                                        $hasError = $errors->has('form.'.$editKey);
                                        $_vw = \Entelechy\Architect\Table\VisibleWhenAlpineCompiler::compile($column);
                                        $canEdit = $this->canEditColumn($column, $isCreate);
                                    @endphp
                                    <div class="mb-3" wire:key="column-{{ $column->key() }}"
                                        @if ($_vw) x-show="{{ $_vw }}" style="display:none" @endif>
                                        @include('architect::table.partials.form-input', ['column' => $column, 'editKey' => $editKey, 'value' => $value, 'type' => $type, 'hasError' => $hasError, 'canEdit' => $canEdit])
                                    </div>
                                @endforeach
                            @elseif ($panelState === 'view')
                                @include('architect::table.partials.view-record', ['viewRecord' => $viewRecord])
                            @elseif ($panelState === 'custom' && $customBlade !== '')
                                @includeIf($customBlade, $customData)
                            @endif
                        </div>

                        <div @class([
                            'arch-panel__footer',
                            'arch-panel__footer--split' => $panelState === 'create' || $panelState === 'edit',
                        ])>
                            @if ($panelState === 'create' || $panelState === 'edit')
                                <button type="button" class="arch-btn arch-btn-link link-secondary" wire:click="close">Cancel</button>
                                <button type="submit" class="arch-btn arch-btn-primary">
                                    <i class="fas fa-save ml-1"></i>
                                    {{ $isCreate ? 'Create' : 'Save changes' }}
                                </button>
                            @elseif ($panelState === 'view')
                                <button type="button" class="arch-btn arch-btn-link link-secondary" wire:click="close">Close</button>
                            @endif
                            {{-- custom state: partial manages its own footer actions --}}
                        </div>

                    @if ($panelState === 'create' || $panelState === 'edit')
                    </form>
                    @endif
            </div>
        </div>
    </div>

@else
    {{-- ── Slide-over mode: Architect slide-over panel ─────────────────────────────── --}}
    <div
        x-data="{ open: @entangle('open').live }"
        x-effect="
            if (open) {
                document.body.classList.add('modal-open');
            } else {
                document.body.classList.remove('modal-open');
            }
        "
    >
        {{-- Backdrop --}}
        <div
            class="arch-panel-backdrop"
            x-show="open"
            x-transition.opacity
            @click="$wire.close()"
            style="display:none"
        ></div>

        {{-- Slide-over panel --}}
        <div
            class="arch-panel arch-panel--slide-over"
            x-show="open"
            style="display:none"
            aria-hidden="false"
        >
            <div
                class="arch-panel__window arch-panel__window--slide-over"
                role="dialog"
                aria-modal="true"
                aria-labelledby="module-table-panel-title"
                x-transition:enter="transform transition arch-ease-spring duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
            @if ($panelState === 'create' || $panelState === 'edit')
            <form wire:submit.prevent="submit">
            @endif

                <div class="arch-panel__header">
                    <div class="arch-panel__header-copy">
                        <h2 class="arch-panel__title" id="module-table-panel-title">{{ $panelTitle }}</h2>
                    </div>
                    <button
                        type="button"
                        class="arch-panel__close"
                        aria-label="Close"
                        wire:click="close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="arch-panel__body">
                    @if ($errorMessage && ($panelState === 'create' || $panelState === 'edit'))
                        <div class="arch-alert arch-alert-danger"
                            role="alert"
                            @if (config('architect.animations', true) && $definition->animateErrors)
                            x-data
                            x-init="$watch('$wire.errorMessage', v => {
                                if (!v) return;
                                $el.classList.remove('arch-shake');
                                void $el.offsetWidth;
                                $el.classList.add('arch-shake');
                            })"
                            @endif
                        >{{ $errorMessage }}</div>
                    @endif

                    @if ($panelState === 'create' || $panelState === 'edit')
                        @foreach ($columns as $column)
                            @php
                                $editKey = $column->getEditKey();
                                $value = $form[$editKey] ?? null;
                                $type = $column->getType() ?? 'text';
                                $hasError = $errors->has('form.'.$editKey);
                                $_vw = \Entelechy\Architect\Table\VisibleWhenAlpineCompiler::compile($column);
                                $canEdit = $this->canEditColumn($column, $isCreate);
                            @endphp
                            <div class="mb-3" wire:key="column-{{ $column->key() }}"
                                @if ($_vw) x-show="{{ $_vw }}" style="display:none" @endif>
                                @include('architect::table.partials.form-input', ['column' => $column, 'editKey' => $editKey, 'value' => $value, 'type' => $type, 'hasError' => $hasError, 'canEdit' => $canEdit])
                            </div>
                        @endforeach
                    @elseif ($panelState === 'view')
                        @include('architect::table.partials.view-record', ['viewRecord' => $viewRecord])
                    @elseif ($panelState === 'custom' && $customBlade !== '')
                        @includeIf($customBlade, $customData)
                    @endif
                </div>

                <div class="arch-panel__footer">
                    @if ($panelState === 'create' || $panelState === 'edit')
                        <button type="button" class="arch-btn arch-btn-link link-secondary" wire:click="close">Cancel</button>
                        <button type="submit" class="arch-btn arch-btn-primary">
                            <i class="fas fa-save ml-1"></i>
                            {{ $isCreate ? 'Create' : 'Save changes' }}
                        </button>
                    @elseif ($panelState === 'view')
                        <button type="button" class="arch-btn arch-btn-link link-secondary" wire:click="close">Close</button>
                    @endif
                    {{-- custom state: partial manages its own footer actions --}}
                </div>

            @if ($panelState === 'create' || $panelState === 'edit')
            </form>
            @endif
            </div>
        </div>
    </div>
@endif
</div>
