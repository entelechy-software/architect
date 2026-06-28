@php
/**
 * @var \Entelechy\Architect\Panels\Panels\QuickFormPanel $panel
 * @var \Entelechy\Architect\Panels\ArchitectPanelDefinition $def
 * @var int $panelIndex
 * @var \Entelechy\Architect\Panels\Livewire\PanelEngine $this
 */
@endphp

<div class="arch-panel arch-panel--quick-form">
    @if ($def->title)
        <h3 class="arch-panel__title">{{ $def->title }}</h3>
    @endif

    @if ($quickFormSuccess[$panelIndex] ?? false)
        <x-architect::callout type="success">{{ $panel->getSuccessMessage() }}</x-architect::callout>
    @endif

    <form wire:submit.prevent="submitQuickForm({{ $panelIndex }})">
        @foreach ($panel->getStructure() as $item)
            @include('architect::forms.partials.structure-item', ['item' => $item])
        @endforeach

        <button type="submit"
                class="arch-button"
                data-variant="solid"
                data-color="primary"
                wire:loading.attr="disabled"
                wire:target="submitQuickForm({{ $panelIndex }})">
            <span wire:loading.remove wire:target="submitQuickForm({{ $panelIndex }})">{{ __('Save') }}</span>
            <span wire:loading wire:target="submitQuickForm({{ $panelIndex }})">{{ __('Saving…') }}</span>
        </button>
    </form>
</div>
