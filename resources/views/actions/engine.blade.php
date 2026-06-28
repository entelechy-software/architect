@php
/**
 * @var \Entelechy\Architect\Actions\Contracts\ArchitectAction|null $action
 * @var array<int, mixed> $structure
 * @var \Entelechy\Architect\Actions\Livewire\ActionEngine $this
 */
@endphp

{{--
    Livewire requires exactly one root element per component render, so
    everything below is wrapped in this div — none of the panels are
    visible simultaneously, but the wrapper must always be present even
    when no action is active.

    The three slide-over panels are conditionally present in the DOM
    based on server-side $openPanel state (set by triggerAction()), so
    they don't need the standalone <x-architect::slide-over> component's
    own client-side x-show toggle — Livewire's morph already adds/removes
    the whole block.
--}}
<div class="arch-action-engine">
{{-- Reusable-class form panel (CreateAction/EditAction ->formClass()) --}}
@if ($openPanel === 'form-class' && $action !== null && method_exists($action, 'getFormClass') && $action->getFormClass() !== null)
    <div class="arch-slide-over-backdrop" wire:click="closePanel"></div>
    <div class="arch-slide-over" data-width="md">
        <div class="arch-slide-over-header">
            <span class="arch-slide-over-title">{{ $action->getLabel() }}</span>
        </div>
        <div class="arch-slide-over-body">
            <livewire:architect-form-engine
                :definition-class="$action->getFormClass()"
                :key="'action-form-'.$activeActionClass.'-'.($activeRecordId ?? 'new')"
            />
        </div>
    </div>
@endif

{{-- Inline-structure form panel (CreateAction/EditAction ->form([...])) --}}
@if ($openPanel === 'inline-form' && $action !== null)
    <div class="arch-slide-over-backdrop" wire:click="closePanel"></div>
    <div class="arch-slide-over" data-width="md">
        <div class="arch-slide-over-header">
            <span class="arch-slide-over-title">{{ $action->getLabel() }}</span>
        </div>
        <form wire:submit.prevent="submitInlineForm">
            <div class="arch-slide-over-body">
                @foreach ($structure as $item)
                    @include('architect::forms.partials.structure-item', ['item' => $item])
                @endforeach
            </div>
            <div class="arch-slide-over-footer">
                <button type="button" class="arch-button" data-variant="ghost" data-color="secondary" wire:click="closePanel">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="arch-button" data-variant="solid" data-color="{{ $action->getColor() }}" wire:loading.attr="disabled" wire:target="submitInlineForm">
                    <span wire:loading.remove wire:target="submitInlineForm">{{ $action->getLabel() }}</span>
                    <span wire:loading wire:target="submitInlineForm">{{ __('Saving…') }}</span>
                </button>
            </div>
        </form>
    </div>
@endif

{{-- Read-only content panel (ViewAction ->contentClass()) --}}
@if ($openPanel === 'content-class' && $action !== null && method_exists($action, 'getContentClass') && $action->getContentClass() !== null)
    <div class="arch-slide-over-backdrop" wire:click="closePanel"></div>
    <div class="arch-slide-over" data-width="md">
        <div class="arch-slide-over-header">
            <span class="arch-slide-over-title">{{ $action->getLabel() }}</span>
        </div>
        <div class="arch-slide-over-body">
            <livewire:architect-content-engine
                :definition-class="$action->getContentClass()"
                :key="'action-content-'.$activeActionClass.'-'.($activeRecordId ?? 'new')"
            />
        </div>
        <div class="arch-slide-over-footer">
            <button type="button" class="arch-button" data-variant="ghost" data-color="secondary" wire:click="closePanel">
                {{ __('Close') }}
            </button>
        </div>
    </div>
@endif

{{-- Confirmation dialog --}}
@if ($showConfirmation && $action !== null)
    <div class="arch-modal-overlay" aria-modal="true" role="dialog">
        <div class="arch-modal arch-modal--sm">
            <div class="arch-modal__header">
                <h3 class="arch-modal__title">{{ $action->getConfirmationTitle() }}</h3>
            </div>
            <div class="arch-modal__body">
                <p>{{ $action->getConfirmationMessage() }}</p>
            </div>
            <div class="arch-modal__footer">
                <button
                    type="button"
                    class="arch-button"
                    data-variant="ghost"
                    data-color="secondary"
                    wire:click="cancelConfirmation"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="button"
                    class="arch-button"
                    data-variant="solid"
                    data-color="{{ $action->isDestructive() ? 'danger' : $action->getColor() }}"
                    wire:click="confirmAndRun"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="confirmAndRun">{{ $action->getLabel() }}</span>
                    <span wire:loading wire:target="confirmAndRun">{{ __('Running…') }}</span>
                </button>
            </div>
        </div>
    </div>
@endif
</div>
