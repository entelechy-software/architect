@php /** @var \Entelechy\Architect\Forms\Fields\ApiRequestBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-api-request-builder">
        <select class="arch-select" wire:model="formData.{{ $field->getName() }}.method">
            @foreach ($field->getMethods() as $method)
                <option value="{{ $method }}">{{ $method }}</option>
            @endforeach
        </select>
        <input type="text" class="arch-input" placeholder="{{ __('URL') }}" wire:model="formData.{{ $field->getName() }}.url">
        <textarea class="arch-input arch-input--code" rows="3" placeholder="{{ __('Body (JSON)') }}" wire:model="formData.{{ $field->getName() }}.body"></textarea>
    </div>
</x-architect::field-wrapper>
