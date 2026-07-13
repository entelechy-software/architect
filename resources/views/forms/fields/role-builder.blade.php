@php /** @var \Entelechy\Architect\Forms\Fields\RoleBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-role-builder">
        <select class="arch-select" multiple wire:model="formData.{{ $field->getName() }}.permissions">
            @foreach ($field->getAvailablePermissions() as $permission)
                <option value="{{ $permission }}">{{ $permission }}</option>
            @endforeach
        </select>
        <select class="arch-select" wire:model="formData.{{ $field->getName() }}.inherits_from">
            <option value="">{{ __('None') }}</option>
            @foreach ($field->getAvailableRolesToInheritFrom() as $role)
                <option value="{{ $role }}">{{ $role }}</option>
            @endforeach
        </select>
    </div>
</x-architect::field-wrapper>
