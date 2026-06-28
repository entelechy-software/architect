@php /** @var \Entelechy\Architect\Forms\Fields\Fieldset $field */ @endphp
<fieldset class="arch-fieldset">
    @if ($field->getLabel() !== '')
        <legend class="arch-fieldset__legend">{{ $field->getLabel() }}</legend>
    @endif
    <div class="arch-grid" data-cols="{{ $field->getColumns() }}" data-gap="md">
        @foreach ($field->getStructure() as $child)
            @include('architect::forms.partials.structure-item', ['item' => $child, 'get' => $get])
        @endforeach
    </div>
</fieldset>
