@php /** @var \Entelechy\Architect\Forms\Fields\Toggle $field */ @endphp
<div class="arch-field" data-type="toggle">
    <div class="arch-toggle" x-data="{ on: $wire.entangle('formData.{{ $field->getName() }}') }">
        <label class="arch-toggle__row">
            <button type="button"
                    class="arch-toggle__switch"
                    role="switch"
                    :aria-checked="on"
                    :data-on="on"
                    @click="on = !on">
                <span class="arch-toggle__thumb"></span>
            </button>
            <span class="arch-toggle__label" x-text="on ? '{{ $field->getOnLabel() }}' : '{{ $field->getOffLabel() }}'"></span>
        </label>
        <span class="arch-field__label">{{ $field->getLabel() }}</span>
    </div>

    @if ($field->getHint() !== null)
        <div class="arch-field__hint">{{ $field->getHint() }}</div>
    @endif

    @error('formData.' . $field->getName())
        <div class="arch-field__error">{{ $message }}</div>
    @enderror
</div>
