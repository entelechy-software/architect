@php /** @var \Entelechy\Architect\Forms\Fields\DiffMergeField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <table class="arch-diff-merge">
        <thead>
            <tr>
                <th>{{ __('Field') }}</th>
                <th>{{ __('Current') }}</th>
                <th>{{ __('Incoming') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($field->getCurrent() as $key => $currentValue)
                <tr>
                    <td>{{ $key }}</td>
                    <td>
                        <label>
                            <input type="radio" name="formData.{{ $field->getName() }}.{{ $key }}" value="current" wire:model="formData.{{ $field->getName() }}.{{ $key }}">
                            {{ $currentValue }}
                        </label>
                    </td>
                    <td>
                        <label>
                            <input type="radio" name="formData.{{ $field->getName() }}.{{ $key }}" value="incoming" wire:model="formData.{{ $field->getName() }}.{{ $key }}">
                            {{ $field->getIncoming()[$key] ?? '' }}
                        </label>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-architect::field-wrapper>
