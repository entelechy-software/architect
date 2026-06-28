@php /** @var \Entelechy\Architect\Forms\Fields\DisplayField $field */ @endphp
<div class="arch-field" data-type="display">
    <span class="arch-field__label">{{ $field->getLabel() }}</span>
    <div class="arch-field__control arch-field__static">{{ $field->getDefault() }}</div>

    @if ($field->getHint() !== null)
        <div class="arch-field__hint">{{ $field->getHint() }}</div>
    @endif
</div>
