{{--
    ModuleTable CSV Import Wizard.

    Single Livewire-driven Architect modal that hosts the entire
    upload → preview → confirm → result flow plus the History panel.
    Rendered once per page (alongside the engine) and toggled open
    via the `architect:open-import` browser event.

    Variables:
      $importColumns — array<string, Column>, ordered, keyed by column key
                       (resolved from the active definition; empty when modal closed)
--}}
@php
    /** @var array<string, \Entelechy\Architect\Table\Column> $importColumns */
@endphp

<div>
    @if ($open)
        <div class="arch-panel-backdrop" wire:key="import-wizard-backdrop"></div>
        <div
            class="arch-panel arch-panel--modal"
            wire:key="import-wizard-modal"
            aria-hidden="false"
        >
            <div
                class="arch-panel__window arch-panel__window--modal arch-panel__window--xl"
                aria-modal="true"
                role="dialog"
                aria-labelledby="module-table-import-title"
            >

                    {{-- ── Header ─────────────────────────────────────── --}}
                    <div class="arch-panel__header">
                        <div class="arch-panel__header-copy">
                            <h2 class="arch-panel__title" id="module-table-import-title">
                                @if ($historyOpen)
                                    <i class="fas fa-history ml-2"></i>Import History
                                @else
                                    <i class="fas fa-file-import ml-2"></i>Import CSV
                                @endif
                            </h2>
                            @if (! $historyOpen)
                                <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    <span>Step {{ $step }} of 4</span>
                                    @if ($step >= 2)
                                        <span><i class="fas fa-list-check ml-1"></i>{{ $this->selectedCount }} selected</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-2 items-center">
                            @if (!$historyOpen)
                                <button type="button" class="arch-btn arch-btn-sm arch-btn-outline-secondary" wire:click="showHistory">
                                    <i class="fas fa-history ml-1"></i>History
                                </button>
                            @else
                                <button type="button" class="arch-btn arch-btn-sm arch-btn-outline-secondary" wire:click="hideHistory">
                                    <i class="fas fa-arrow-left ml-1"></i>Back to wizard
                                </button>
                            @endif
                            <button type="button" class="arch-panel__close" wire:click="closeWizard" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>

                    {{-- ── Body ───────────────────────────────────────── --}}
                    <div class="arch-panel__body">
                        @if ($errorMessage)
                            <div class="arch-alert arch-alert-danger flex items-center" role="alert">
                                <i class="fas fa-exclamation-triangle ml-2"></i>
                                <div>{{ $errorMessage }}</div>
                            </div>
                        @endif

                        @if ($successMessage && $step === 4)
                            <div class="arch-alert arch-alert-success flex items-center" role="alert">
                                <i class="fas fa-check-circle ml-2"></i>
                                <div>{{ $successMessage }}</div>
                            </div>
                        @endif

                        @if ($historyOpen)
                            {{-- ─── History panel ─────────────────────── --}}
                            @include('architect::table.import-wizard._history', ['batches' => $batches])
                        @else
                            @switch($step)
                                @case(1)
                                    @include('architect::table.import-wizard._step-upload', ['importColumns' => $importColumns, 'globalErrors' => $globalErrors])
                                    @break

                                @case(2)
                                    @include('architect::table.import-wizard._step-preview', ['importColumns' => $importColumns])
                                    @break

                                @case(3)
                                    @include('architect::table.import-wizard._step-confirm')
                                    @break

                                @case(4)
                                    @include('architect::table.import-wizard._step-result')
                                    @break
                            @endswitch
                        @endif
                    </div>

                    {{-- ── Footer ─────────────────────────────────────── --}}
                    @unless ($historyOpen)
                        <div class="arch-panel__footer">
                            @if ($step === 1)
                                <button type="button" class="arch-btn arch-btn-secondary" wire:click="closeWizard">Cancel</button>
                                <button type="button" class="arch-btn arch-btn-primary"
                                    wire:click="processUpload"
                                    wire:loading.attr="disabled"
                                    @disabled(!$file)
                                >
                                    <span wire:loading.remove wire:target="processUpload">
                                        <i class="fas fa-upload ml-1"></i>Upload &amp; Preview
                                    </span>
                                    <span wire:loading wire:target="processUpload">
                                        <i class="fas fa-spinner fa-spin ml-1"></i>Parsing…
                                    </span>
                                </button>
                            @elseif ($step === 2)
                                <button type="button" class="arch-btn arch-btn-secondary" wire:click="closeWizard">Cancel</button>
                                <button type="button" class="arch-btn arch-btn-primary"
                                    wire:click="goToConfirm"
                                    @disabled(!$this->canCommit)
                                >
                                    Continue
                                    <i class="fas fa-arrow-right mr-1"></i>
                                </button>
                            @elseif ($step === 3)
                                <button type="button" class="arch-btn arch-btn-secondary" wire:click="backToPreview">
                                    <i class="fas fa-arrow-left ml-1"></i>Back
                                </button>
                                <button type="button" class="arch-btn arch-btn-success"
                                    wire:click="commitImport"
                                    wire:loading.attr="disabled"
                                    @disabled(!$this->canCommit)
                                >
                                    <span wire:loading.remove wire:target="commitImport">
                                        <i class="fas fa-check ml-1"></i>Confirm &amp; Import
                                    </span>
                                    <span wire:loading wire:target="commitImport">
                                        <i class="fas fa-spinner fa-spin ml-1"></i>Importing…
                                    </span>
                                </button>
                            @elseif ($step === 4)
                                <button type="button" class="arch-btn arch-btn-outline-secondary" wire:click="showHistory">
                                    <i class="fas fa-history ml-1"></i>View History
                                </button>
                                <button type="button" class="arch-btn arch-btn-primary" wire:click="closeWizard">Close</button>
                            @endif
                        </div>
                    @endunless

            </div>
        </div>
    @endif
</div>
