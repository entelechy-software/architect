@php
/**
 * @var \Entelechy\Architect\Forms\ArchitectFormDefinition $definition
 * @var \Closure(string): mixed $get
 */
@endphp
<form
    class="arch-form"
    wire:submit.prevent="submit"
    @if ($definition->autosaveInterval) wire:poll.{{ $definition->autosaveInterval }}s="autosave" @endif
>
    @foreach ($definition->structure as $item)
        @include('architect::forms.partials.structure-item', ['item' => $item, 'get' => $get])
    @endforeach

    <div class="arch-form__actions">
        <button type="submit" class="arch-button" data-variant="solid" data-color="primary" data-size="md" wire:loading.attr="disabled" wire:target="submit">
            <span wire:loading.remove wire:target="submit">{{ __('Save') }}</span>
            <span wire:loading wire:target="submit">{{ __('Savingâ¦') }}</span>
        </button>

        @if ($justSaved)
            <span class="arch-badge" data-color="success" data-variant="soft">{{ __('Saved') }}</span>
        @endif
    </div>
</form>
