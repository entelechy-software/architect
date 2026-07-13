@php /** @var \Entelechy\Architect\Forms\Fields\PermissionMatrixField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <table class="arch-permission-matrix">
        <thead>
            <tr>
                <th></th>
                @foreach ($field->getActions() as $action)
                    <th>{{ ucfirst($action) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($field->getResources() as $resource)
                <tr>
                    <td>{{ $resource }}</td>
                    @foreach ($field->getActions() as $action)
                        <td>
                            <input type="checkbox" wire:model="formData.{{ $field->getName() }}.{{ $resource }}.{{ $action }}">
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</x-architect::field-wrapper>
