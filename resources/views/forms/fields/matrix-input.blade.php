@php /** @var \Entelechy\Architect\Forms\Fields\MatrixInputField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <table class="arch-matrix-input">
        <thead>
            <tr>
                <th></th>
                @foreach ($field->getColumns() as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($field->getRows() as $row)
                <tr>
                    <td>{{ $row }}</td>
                    @foreach ($field->getColumns() as $column)
                        <td>
                            <input type="radio"
                                   name="formData.{{ $field->getName() }}.{{ $row }}"
                                   value="{{ $column }}"
                                   wire:model="formData.{{ $field->getName() }}.{{ $row }}">
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</x-architect::field-wrapper>
